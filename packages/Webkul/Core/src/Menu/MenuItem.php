<?php

namespace Webkul\Core\Menu;

use Illuminate\Support\Collection;

class MenuItem
{
    
    public function __construct(
        public string $key,
        public string $na,
        public string $route,
        public int $sort,
        public string $icon,
        public Collection $children,
    ) {}

    
    public function getName(): string
    {
        return $this->name;
    }

    
    public function getIcon(): string
    {
        return $this->icon;
    }

    
    public function getRoute(): string
    {
        return $this->route;
    }

    
    public function getUrl(): string
    {
        return route($this->getRoute());
    }

    
    public function getKey(): string
    {
        return $this->key;
    }

    
    public function haveChildren(): bool
    {
        return $this->children->isNotEmpty();
    }

    
    public function getChildren(): Collection
    {
        if (! $this->haveChildren()) {
            return collect();
        }

        return $this->children;
    }

    
    public function isActive(): bool
    {
        if (request()->fullUrlIs($this->getUrl().'*')) {
            return true;
        }

        if ($this->haveChildren()) {
            foreach ($this->getChildren() as $child) {
                if ($child->isActive()) {
                    return true;
                }
            }
        }

        return false;
    }
}
