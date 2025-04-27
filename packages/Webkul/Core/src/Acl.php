<?php

namespace Webkul\Core;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Webkul\Core\Acl\AclItem;

class Acl
{
    
    protected array $items = [];

    
    public function addItem(AclItem $aclItem): void
    {
        $this->items[] = $aclItem;
    }

    
    public function getItems(): Collection
    {
        if (! $this->items) {
            $this->prepareAclItems();
        }

        return collect($this->items)
            ->sortBy('sort');
    }

    
    private function getAclConfig(): array
    {
        static $aclConfig;

        if ($aclConfig) {
            return $aclConfig;
        }

        $aclConfig = config('acl');

        return $aclConfig;
    }

    
    public function getRoles(): Collection
    {
        static $roles;

        if ($roles) {
            return $roles;
        }

        $roles = collect($this->getAclConfig())
            ->mapWithKeys(fn ($role) => [$role['route'] => $role['key']]);

        return $roles;
    }

    
    private function prepareAclItems(): void
    {
        $aclWithDotNotation = [];

        foreach ($this->getAclConfig() as $item) {
            $aclWithDotNotation[$item['key']] = $item;
        }

        $acl = Arr::undot(Arr::dot($aclWithDotNotation));

        foreach ($acl as $aclItemKey => $aclItem) {
            $subAclItems = $this->processSubAclItems($aclItem);

            $this->addItem(new AclItem(
                key: $aclItemKey,
                name: trans($aclItem['name']),
                route: $aclItem['route'],
                sort: $aclItem['sort'],
                children: $subAclItems,
            ));
        }
    }

    
    private function processSubAclItems($aclItem): Collection
    {
        return collect($aclItem)
            ->sortBy('sort')
            ->filter(fn ($va) => is_array($va))
            ->map(function ($subAclItem) {
                $subSubAclItems = $this->processSubAclItems($subAclItem);

                return new AclItem(
                    key: $subAclItem['key'],
                    name: trans($subAclItem['name']),
                    route: $subAclItem['route'],
                    sort: $subAclItem['sort'],
                    children: $subSubAclItems,
                );
            });
    }
}
