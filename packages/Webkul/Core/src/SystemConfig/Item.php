<?php

namespace Webkul\Core\SystemConfig;

use Illuminate\Support\Collection;

class Item
{
    
    public function __construct(
        public Collection $children,
        public ?array $fields,
        public ?string $icon,
        public ?string $icon_class,
        public ?string $info,
        public string $key,
        public string $na,
        public ?string $route = null,
        public ?int $sort = null
    ) {}

    
    public function getName(): string
    {
        return $this->name ?? '';
    }

    
    private function formatOptions($options)
    {
        return is_array($options) ? $options : (is_string($options) ? $options : []);
    }

    
    public function getFields(): Collection
    {
        return collect($this->fields)->map(function ($field) {
            return new ItemField(
                item_key: $this->key,
                name: $field['name'],
                title: $field['title'],
                info: $field['info'] ?? null,
                type: $field['type'],
                depends: $field['depends'] ?? null,
                path: $field['path'] ?? null,
                validation: $field['validation'] ?? null,
                default: $field['default'] ?? null,
                channel_based: $field['channel_based'] ?? null,
                locale_based: $field['locale_based'] ?? null,
                options: $this->formatOptions($field['options'] ?? null),
                is_visible: true,
            );
        });
    }

    
    public function getInfo(): ?string
    {
        return $this->info;
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

    
    public function getIcon(): ?string
    {
        return $this->icon;
    }

    
    public function getIconClass(): ?string
    {
        return $this->icon_class;
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
}
