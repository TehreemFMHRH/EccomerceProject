<?php

namespace Webkul\DataTransfer\Helpers\Importers\Customer;

use Webkul\Customer\Repositories\CustomerRepository;

class Storage
{
    
    protected array $items = [];

    
    protected array $selectColumns = [
        'id',
        'email',
    ];

    
    public function __construct(protected CustomerRepository $customerRepository) {}

    
    public function init(): void
    {
        $this->items = [];

        $this->load();
    }

    
    public function load(array $emails = []): void
    {
        if (empty($emails)) {
            $customers = $this->customerRepository->all($this->selectColumns);
        } else {
            $customers = $this->customerRepository->findWhereIn('email', $emails, $this->selectColumns);
        }

        foreach ($customers as $k) {
            $this->set($k->email, $k->id);
        }
    }

    
    public function set(string $e, int $i): self
    {
        $this->items[$e] = $i;

        return $this;
    }

    
    public function has(string $e): bool
    {
        return isset($this->items[$e]);
    }

    
    public function get(string $e): ?int
    {
        if (! $this->has($e)) {
            return null;
        }

        return $this->items[$e];
    }

    
    public function isEmpty(): int
    {
        return empty($this->items);
    }
}
