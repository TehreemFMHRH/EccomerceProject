<?php

namespace Webkul\DataTransfer\Helpers\Sources;

use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;

abstract class AbstractSource
{
    
    protected mixed $reader;

    
    protected array $columnNames = [];

    
    protected int $totalColumns = 0;

    
    protected array $currentRowData = [];

    
    protected int $currentRowNumber = -1;

    
    protected bool $foundWrongQuoteFlag = false;

    
    abstract protected function initialize(): void;

    
    abstract protected function getNextRow(): array|bool;

    
    abstract public function generateErrorReport(array $errors): string;

    
    public function __construct(
        protected string $filePath,
        protected string $delimiter = ','
    ) {
        try {
            $this->initialize();
        } catch (\Exception $e) {
            throw new \LogicException("Unable to open file: '{$filePath}'");
        }
    }

    
    public function getCurrentRowNumber(): int
    {
        return $this->currentRowNumber;
    }

    
    public function valid(): bool
    {
        return $this->currentRowNumber !== -1;
    }

    
    public function current(): array
    {
        $row = $this->currentRowData;

        if (count($row) != $this->totalColumns) {
            if ($this->foundWrongQuoteFlag) {
                throw new \InvalidArgumentException(AbstractImporter::ERROR_CODE_WRONG_QUOTES);
            } else {
                throw new \InvalidArgumentException(AbstractImporter::ERROR_CODE_COLUMNS_NUMBER);
            }
        }

        return array_combine($this->columnNames, $row);
    }

    
    public function next(): void
    {
        $this->currentRowNumber++;

        $row = $this->getNextRow();

        if ($row === false || $row === []) {
            $this->currentRowData = [];

            $this->currentRowNumber = -1;
        } else {
            $this->currentRowData = $row;
        }
    }

    
    public function rewind(): void
    {
        $this->currentRowNumber = 0;

        $this->currentRowData = [];

        $this->getNextRow();

        $this->next();
    }

    
    public function setReader(mixed $reader): void
    {
        $this->reader = $reader;
    }

    
    public function getReader(): mixed
    {
        return $this->reader;
    }

    
    public function setColumnNames(array $columnNames): void
    {
        $this->columnNames = $columnNames;
    }

    
    public function getColumnNames(): array
    {
        return $this->columnNames;
    }

    
    public function setTotalColumns(int $totalColumns): void
    {
        $this->totalColumns = $totalColumns;
    }

    
    public function getTotalColumns(): int
    {
        return $this->totalColumns;
    }

    
    public function errorFilePath(): string
    {
        $fileType = pathinfo($this->filePath, PATHINFO_EXTENSION);

        return 'imports/'.time().'-error-report.'.$fileType;
    }
}
