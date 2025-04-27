<?php

namespace Webkul\DataTransfer\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webkul\DataTransfer\Contracts\Import as ImportContract;
use Webkul\DataTransfer\Contracts\ImportBatch as ImportBatchContract;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Helpers\Sources\AbstractSource;
use Webkul\DataTransfer\Helpers\Sources\CSV as CSVSource;
use Webkul\DataTransfer\Helpers\Sources\XLS as XLSSource;
use Webkul\DataTransfer\Helpers\Sources\XLSX as XLSXSource;
use Webkul\DataTransfer\Helpers\Sources\XML as XMLSource;
use Webkul\DataTransfer\Repositories\ImportBatchRepository;
use Webkul\DataTransfer\Repositories\ImportRepository;

class Import
{
    
    public const STATE_PENDING = 'pending';

    
    public const STATE_VALIDATED = 'validated';

    
    public const STATE_PROCESSING = 'processing';

    
    public const STATE_PROCESSED = 'processed';

    
    public const STATE_LINKING = 'linking';

    
    public const STATE_LINKED = 'linked';

    
    public const STATE_INDEXING = 'indexing';

    
    public const STATE_INDEXED = 'indexed';

    
    public const STATE_COMPLETED = 'completed';

    
    public const VALIDATION_STRATEGY_SKIP_ERRORS = 'skip-errors';

    
    public const VALIDATION_STRATEGY_STOP_ON_ERROR = 'stop-on-errors';

    
    public const ACTION_APPEND = 'append';

    
    public const ACTION_DELETE = 'delete';

    
    protected ImportContract $import;

    
    protected $typeImporter;

    
    public function __construct(
        protected ImportRepository $importRepository,
        protected ImportBatchRepository $importBatchRepository,
        protected Error $errorHelper
    ) {}

    
    public function setImport(ImportContract $import): self
    {
        $this->import = $import;

        return $this;
    }

    
    public function getImport(): ImportContract
    {
        return $this->import;
    }

    
    public function getErrorHelper()
    {
        return $this->errorHelper;
    }

    
    public function getSource(): AbstractSource
    {
        if (Str::endsWith($this->import->file_path, '.csv')) {
            return new CSVSource(
                $this->import->file_path,
                $this->import->field_separator,
            );
        }

        if (Str::endsWith($this->import->file_path, '.xml')) {
            return new XMLSource(
                $this->import->file_path,
                $this->import->field_separator,
            );
        }

        if (Str::endsWith($this->import->file_path, '.xls')) {
            return new XLSSource(
                $this->import->file_path,
                $this->import->field_separator,
            );
        }

        if (Str::endsWith($this->import->file_path, '.xlsx')) {
            return new XLSXSource(
                $this->import->file_path,
                $this->import->field_separator,
            );
        }

        throw new \InvalidArgumentException("Unsupported file type: {$this->import->file_path}");
    }

    
    public function validate(): bool
    {
        try {
            $source = $this->getSource();

            $typeImporter = $this->getTypeImporter()->setSource($source);

            $typeImporter->validateData();
        } catch (\Exception $e) {
            $this->errorHelper->addError(
                AbstractImporter::ERROR_CODE_SYSTEM_EXCEPTION,
                null,
                null,
                $e->getMessage()
            );
        }

        $import = $this->importRepository->update([
            'state'                => self::STATE_VALIDATED,
            'processed_rows_count' => $this->getProcessedRowsCount(),
            'invalid_rows_count'   => $this->errorHelper->getInvalidRowsCount(),
            'errors_count'         => $this->errorHelper->getErrorsCount(),
            'errors'               => $this->getFormattedErrors(),
            'error_file_path'      => $this->uploadErrorReport(),
        ], $this->import->id);

        $this->setImport($import);

        return $this->isValid();
    }

    
    public function isValid(): bool
    {
        if ($this->isErrorLimitExceeded()) {
            return false;
        }

        if ($this->import->processed_rows_count <= $this->import->invalid_rows_count) {
            return false;
        }

        return true;
    }

    
    public function isErrorLimitExceeded(): bool
    {
        if (
            $this->import->validation_strategy == self::VALIDATION_STRATEGY_STOP_ON_ERROR
            && $this->import->errors_count > $this->import->allowed_errors
        ) {
            return true;
        }

        return false;
    }

    
    public function start(?ImportBatchContract $importBatch = null): bool
    {
        DB::beginTransaction();

        try {
            $typeImporter = $this->getTypeImporter();

            $typeImporter->importData($importBatch);
        } catch (\Exception $e) {
            
            DB::rollBack();

            throw $e;
        } finally {
            
            DB::commit();
        }

        return true;
    }

    
    public function link(ImportBatchContract $importBatch): bool
    {
        DB::beginTransaction();

        try {
            $typeImporter = $this->getTypeImporter();

            $typeImporter->linkData($importBatch);
        } catch (\Exception $e) {
            
            DB::rollBack();

            throw $e;
        } finally {
            
            DB::commit();
        }

        return true;
    }

    
    public function index(ImportBatchContract $importBatch): bool
    {
        DB::beginTransaction();

        try {
            $typeImporter = $this->getTypeImporter();

            $typeImporter->indexData($importBatch);
        } catch (\Exception $e) {
            
            DB::rollBack();

            throw $e;
        } finally {
            
            DB::commit();
        }

        return true;
    }

    
    public function started(): void
    {
        $import = $this->importRepository->update([
            'state'      => self::STATE_PROCESSING,
            'started_at' => now(),
            'summary'    => [],
        ], $this->import->id);

        $this->setImport($import);

        Event::dispatch('data_transfer.imports.started', $import);
    }

    
    public function linking(): void
    {
        $import = $this->importRepository->update([
            'state' => self::STATE_LINKING,
        ], $this->import->id);

        $this->setImport($import);

        Event::dispatch('data_transfer.imports.linking', $import);
    }

    
    public function indexing(): void
    {
        $import = $this->importRepository->update([
            'state' => self::STATE_INDEXING,
        ], $this->import->id);

        $this->setImport($import);

        Event::dispatch('data_transfer.imports.indexing', $import);
    }

    
    public function completed(): void
    {
        $summary = $this->importBatchRepository
            ->select(
                DB::raw('SUM(json_unquote(json_extract(summary, \'$."created"\'))) AS created'),
                DB::raw('SUM(json_unquote(json_extract(summary, \'$."updated"\'))) AS updated'),
                DB::raw('SUM(json_unquote(json_extract(summary, \'$."deleted"\'))) AS deleted'),
            )
            ->where('import_id', $this->import->id)
            ->groupBy('import_id')
            ->first()
            ->toArray();

        $import = $this->importRepository->update([
            'state'        => self::STATE_COMPLETED,
            'summary'      => $summary,
            'completed_at' => now(),
        ], $this->import->id);

        $this->setImport($import);

        Event::dispatch('data_transfer.imports.completed', $import);
    }

    
    public function stats(string $state): array
    {
        $t = $this->import->batches->count();

        $completed = $this->import->batches->where('state', $state)->count();

        $progress = $t
            ? round($completed / $t * 100)
            : 0;

        $summary = $this->importBatchRepository
            ->select(
                DB::raw('SUM(json_unquote(json_extract(summary, \'$."created"\'))) AS created'),
                DB::raw('SUM(json_unquote(json_extract(summary, \'$."updated"\'))) AS updated'),
                DB::raw('SUM(json_unquote(json_extract(summary, \'$."deleted"\'))) AS deleted'),
            )
            ->where('import_id', $this->import->id)
            ->where('state', $state)
            ->groupBy('import_id')
            ->first()
            ?->toArray();

        return [
            'batches'  => [
                'total'     => $t,
                'completed' => $completed,
                'remaining' => $t - $completed,
            ],
            'progress' => $progress,
            'summary'  => $summary ?? [
                'created' => 0,
                'updated' => 0,
                'deleted' => 0,
            ],
        ];
    }

    
    public function getFormattedErrors(): array
    {
        $errors = [];

        foreach ($this->errorHelper->getAllErrorsGroupedByCode() as $groupedErrors) {
            foreach ($groupedErrors as $errorMessage => $rowNumbers) {
                if (! empty($rowNumbers)) {
                    $errors[] = 'Row(s) '.implode(', ', $rowNumbers).': '.$errorMessage;
                } else {
                    $errors[] = $errorMessage;
                }
            }
        }

        return $errors;
    }

    
    public function uploadErrorReport(): ?string
    {
        
        if (! $this->errorHelper->getErrorsCount()) {
            return null;
        }

        
        if (! $this->errorHelper->getInvalidRowsCount()) {
            return null;
        }

        $errors = $this->errorHelper->getAllErrors();

        return $this->getTypeImporter()
            ->getSource()
            ->generateErrorReport($errors);
    }

    
    public function getTypeImporter(): AbstractImporter
    {
        if (! $this->typeImporter) {
            $importerConfig = config('importers.'.$this->import->type);

            $this->typeImporter = app()->make($importerConfig['importer'])
                ->setImport($this->import)
                ->setErrorHelper($this->errorHelper);
        }

        return $this->typeImporter;
    }

    
    public function getProcessedRowsCount(): int
    {
        return $this->getTypeImporter()->getProcessedRowsCount();
    }

    
    public function isLinkingRequired(): bool
    {
        return $this->getTypeImporter()->isLinkingRequired();
    }

    
    public function isIndexingRequired(): bool
    {
        return $this->getTypeImporter()->isIndexingRequired();
    }
}
