<?php

namespace Webkul\DataTransfer\Helpers\Importers\TaxRate;

use Webkul\Tax\Repositories\TaxRateRepository;

class Storage
{
    
    protected array $items = [];

    
    protected array $selectColumns = [
        'id',
        'identifier',
    ];

    
    public function __construct(protected TaxRateRepository $taxRateRepository) {}

    
    public function init(): void
    {
        $this->items = [];

        $this->load();
    }

    
    public function load(array $identifiers = []): void
    {
        if (empty($identifiers)) {
            $taxRates = $this->taxRateRepository->all($this->selectColumns);
        } else {
            $taxRates = $this->taxRateRepository->findWhereIn('identifier', $identifiers, $this->selectColumns);
        }

        foreach ($taxRates as $taxRate) {
            $this->set($taxRate->identifier, $taxRate->id);
        }
    }

    
    public function set(string $identifier, int $i): self
    {
        $this->items[$identifier] = $i;

        return $this;
    }

    
    public function has(string $identifier): bool
    {
        return isset($this->items[$identifier]);
    }

    
    public function get(string $identifier): ?int
    {
        if (! $this->has($identifier)) {
            return null;
        }

        return $this->items[$identifier];
    }

    
    public function isEmpty(): int
    {
        return empty($this->items);
    }
}
