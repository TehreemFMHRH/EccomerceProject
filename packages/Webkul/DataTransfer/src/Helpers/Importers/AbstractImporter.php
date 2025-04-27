<?php

namespace Webkul\DataTransfer\Helpers\Importers;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Webkul\DataTransfer\Contracts\Import as ImportContract;
use Webkul\DataTransfer\Contracts\ImportBatch as ImportBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Jobs\Import\Completed as CompletedJob;
use Webkul\DataTransfer\Jobs\Import\ImportBatch as ImportBatchJob;
use Webkul\DataTransfer\Jobs\Import\IndexBatch as IndexBatchJob;
use Webkul\DataTransfer\Jobs\Import\Indexing as IndexingJob;
use Webkul\DataTransfer\Jobs\Import\LinkBatch as LinkBatchJob;
use Webkul\DataTransfer\Jobs\Import\Linking as LinkingJob;
use Webkul\DataTransfer\Repositories\ImportBatchRepository;

abstract class AbstractImporter
{
    
    public const ERROR_CODE_SYSTEM_EXCEPTION = 'system_exception';

    
    public const ERROR_CODE_COLUMN_NOT_FOUND = 'column_not_found';

    
    public const ERROR_CODE_COLUMN_EMPTY_HEADER = 'column_empty_header';

    
    public const ERROR_CODE_COLUMN_NAME_INVALID = 'column_name_invalid';

    
    public const ERROR_CODE_INVALID_ATTRIBUTE = 'invalid_attribute_name';

    
    public const ERROR_CODE_WRONG_QUOTES = 'wrong_quotes';

    
    public const ERROR_CODE_COLUMNS_NUMBER = 'wrong_columns_number';

    
    protected array $errorMessages = [
        self::ERROR_CODE_SYSTEM_EXCEPTION    => 'data_transfer::app.validation.errors.system',
        self::ERROR_CODE_COLUMN_NOT_FOUND    => 'data_transfer::app.validation.errors.column-not-found',
        self::ERROR_CODE_COLUMN_EMPTY_HEADER => 'data_transfer::app.validation.errors.column-empty-headers',
        self::ERROR_CODE_COLUMN_NAME_INVALID => 'data_transfer::app.validation.errors.column-name-invalid',
        self::ERROR_CODE_INVALID_ATTRIBUTE   => 'data_transfer::app.validation.errors.invalid-attribute',
        self::ERROR_CODE_WRONG_QUOTES        => 'data_transfer::app.validation.errors.wrong-quotes',
        self::ERROR_CODE_COLUMNS_NUMBER      => 'data_transfer::app.validation.errors.column-numbers',
    ];

    public const BATCH_SIZE = 100;

    
    protected bool $linkingRequired = false;

    
    protected bool $indexingRequired = false;

    
    protected $errorHelper;

    
    protected ImportContract $import;

    
    protected $source;

    
    protected array $validColumnNames = [];

    
    protected array $validatedRows = [];

    
    protected int $processedRowsCount = 0;

    
    protected int $createdItemsCount = 0;

    
    protected int $updatedItemsCount = 0;

    
    protected int $deletedItemsCount = 0;

    
    public function __construct(protected ImportBatchRepository $importBatchRepository) {}

    
    abstract public function validateRow(array $rowData, int $rowNumber): bool;

    
    abstract public function importBatch(ImportBatchContract $importBatchContract): bool;

    
    protected function initErrorMessages(): void
    {
        foreach ($this->errorMessages as $errorCode => $message) {
            $this->errorHelper->addErrorMessage($errorCode, trans($message));
        }
    }

    
    public function setImport(ImportContract $import): self
    {
        $this->import = $import;

        return $this;
    }

    
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    
    public function setErrorHelper($errorHelper): self
    {
        $this->errorHelper = $errorHelper;

        $this->initErrorMessages();

        return $this;
    }

    
    public function getSource()
    {
        return $this->source;
    }

    
    public function getValidColumnNames(): array
    {
        return $this->validColumnNames;
    }

    
    public function validateData(): void
    {
        Event::dispatch('data_transfer.imports.validate.before', $this->import);

        $errors = [];

        $absentColumns = array_diff($this->permanentAttributes, $this->getSource()->getColumnNames());

        if (! empty($absentColumns)) {
            $errors[self::ERROR_CODE_COLUMN_NOT_FOUND] = $absentColumns;
        }

        foreach ($this->getSource()->getColumnNames() as $columnNumber => $columnName) {
            if (empty($columnName)) {
                $errors[self::ERROR_CODE_COLUMN_EMPTY_HEADER][] = $columnNumber + 1;
            } elseif (! preg_match('/^[a-z][a-z0-9_]*$/', $columnName)) {
                $errors[self::ERROR_CODE_COLUMN_NAME_INVALID][] = $columnName;
            } elseif (! in_array($columnName, $this->getValidColumnNames())) {
                $errors[self::ERROR_CODE_INVALID_ATTRIBUTE][] = $columnName;
            }
        }

        
        foreach ($errors as $errorCode => $error) {
            $this->addErrors($errorCode, $error);
        }

        if (! $this->errorHelper->getErrorsCount()) {
            $this->saveValidatedBatches();
        }

        Event::dispatch('data_transfer.imports.validate.after', $this->import);
    }

    
    protected function saveValidatedBatches(): self
    {
        $source = $this->getSource();

        $batchRows = [];

        $source->rewind();

        
        $this->importBatchRepository->deleteWhere([
            'import_id' => $this->import->id,
        ]);

        while (
            $source->valid()
            || count($batchRows)
        ) {
            if (
                count($batchRows) == self::BATCH_SIZE
                || ! $source->valid()
            ) {
                $this->importBatchRepository->create([
                    'import_id' => $this->import->id,
                    'data'      => $batchRows,
                ]);

                $batchRows = [];
            }

            if ($source->valid()) {
                $rowData = $source->current();

                if ($this->validateRow($rowData, $source->getCurrentRowNumber())) {
                    $batchRows[] = $this->prepareRowForDb($rowData);
                }

                $this->processedRowsCount++;

                $source->next();
            }
        }

        return $this;
    }

    
    public function importData(?ImportBatchContract $importBatch = null): bool
    {
        if ($importBatch) {
            $this->importBatch($importBatch);

            return true;
        }

        $typeBatches = [];

        foreach ($this->import->batches as $batch) {
            $typeBatches['import'][] = new ImportBatchJob($batch);

            if ($this->isLinkingRequired()) {
                $typeBatches['link'][] = new LinkBatchJob($batch);
            }

            if ($this->isIndexingRequired()) {
                $typeBatches['index'][] = new IndexBatchJob($batch);
            }
        }

        $chain[] = Bus::batch($typeBatches['import']);

        if (! empty($typeBatches['link'])) {
            $chain[] = new LinkingJob($this->import);

            $chain[] = Bus::batch($typeBatches['link']);
        }

        if (! empty($typeBatches['index'])) {
            $chain[] = new IndexingJob($this->import);

            $chain[] = Bus::batch($typeBatches['index']);
        }

        $chain[] = new CompletedJob($this->import);

        Bus::chain($chain)->dispatch();

        return true;
    }

    
    public function linkData(ImportBatchContract $importBatch): bool
    {
        $this->linkBatch($importBatch);

        return true;
    }

    
    public function indexData(ImportBatchContract $importBatch): bool
    {
        $this->indexBatch($importBatch);

        return true;
    }

    
    protected function addErrors(string $code, mixed $errors): void
    {
        $this->errorHelper->addError(
            $code,
            null,
            implode('", "', $errors)
        );
    }

    
    protected function skipRow($rowNumber, string $errorCode, $columnName = null, $errorMessage = null): self
    {
        $this->errorHelper->addError(
            $errorCode,
            $rowNumber,
            $columnName,
            $errorMessage
        );

        $this->errorHelper->addRowToSkip($rowNumber);

        return $this;
    }

    
    protected function prepareRowForDb(array $rowData): array
    {
        $rowData = array_map(function ($va) {
            return $va === '' ? null : $va;
        }, $rowData);

        return $rowData;
    }

    
    public function getProcessedRowsCount(): int
    {
        return $this->processedRowsCount;
    }

    
    public function getCreatedItemsCount(): int
    {
        return $this->createdItemsCount;
    }

    
    public function getUpdatedItemsCount(): int
    {
        return $this->updatedItemsCount;
    }

    
    public function getDeletedItemsCount(): int
    {
        return $this->deletedItemsCount;
    }

    
    public function isLinkingRequired(): bool
    {
        if ($this->import->action == Import::ACTION_DELETE) {
            return false;
        }

        return $this->linkingRequired;
    }

    
    public function isIndexingRequired(): bool
    {
        if ($this->import->action == Import::ACTION_DELETE) {
            return false;
        }

        return $this->indexingRequired;
    }
}
