<?php

namespace Webkul\DataTransfer\Helpers;

class Error
{
    
    protected array $items = [];

    
    protected array $invalidRows = [];

    
    protected array $skippedRows = [];

    
    protected int $errorsCount = 0;

    
    protected array $messageTemplate = [];

    
    public function addErrorMessage(string $code, string $template): self
    {
        $this->messageTemplate[$code] = $template;

        return $this;
    }

    
    public function addError(string $code, ?int $rowNumber = null, ?string $columnName = null, ?string $message = null): self
    {
        if ($this->isErrorAlreadyAdded($rowNumber, $code, $columnName)) {
            return $this;
        }

        $this->addRowToInvalid($rowNumber);

        $message = $this->getErrorMessage($code, $message, $columnName);

        $this->items[$rowNumber][] = [
            'code'    => $code,
            'column'  => $columnName,
            'message' => $message,
        ];

        $this->errorsCount++;

        return $this;
    }

    
    public function isErrorAlreadyAdded(?int $rowNumber, string $code, ?string $columnName): bool
    {
        return collect($this->items[$rowNumber] ?? [])
            ->where('code', $code)
            ->where('column', $columnName)
            ->isNotEmpty();
    }

    
    protected function addRowToInvalid(?int $rowNumber): self
    {
        if (is_null($rowNumber)) {
            return $this;
        }

        if (! in_array($rowNumber, $this->invalidRows)) {
            $this->invalidRows[] = $rowNumber;
        }

        return $this;
    }

    
    public function addRowToSkip(?int $rowNumber): self
    {
        if (is_null($rowNumber)) {
            return $this;
        }

        if (! in_array($rowNumber, $this->skippedRows)) {
            $this->skippedRows[] = $rowNumber;
        }

        return $this;
    }

    
    public function isRowInvalid(int $rowNumber): bool
    {
        return in_array($rowNumber, array_merge($this->invalidRows, $this->skippedRows));
    }

    
    protected function getErrorMessage(?string $code, ?string $message, ?string $columnName): string
    {
        if (
            empty($message)
            && isset($this->messageTemplate[$code])
        ) {
            $message = (string) $this->messageTemplate[$code];
        }

        if (
            $columnName
            && $message
        ) {
            $message = sprintf($message, $columnName);
        }

        if (! $message) {
            $message = $code;
        }

        return $message;
    }

    
    public function getInvalidRowsCount(): int
    {
        return count($this->invalidRows);
    }

    
    public function getErrorsCount(): int
    {
        return $this->errorsCount;
    }

    
    public function getAllErrors(): array
    {
        return $this->items;
    }

    
    public function getAllErrorsGroupedByCode(): array
    {
        $errors = [];

        foreach ($this->items as $rowNumber => $rowErrors) {
            foreach ($rowErrors as $error) {
                if ($rowNumber === '') {
                    $errors[$error['code']][$error['message']] = null;
                } else {
                    $errors[$error['code']][$error['message']][] = $rowNumber;
                }
            }
        }

        return $errors;
    }
}
