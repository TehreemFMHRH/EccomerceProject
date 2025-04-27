<?php

namespace Webkul\Core\SystemConfig;

use Illuminate\Support\Str;

class ItemField
{
    
    protected $veeValidateMappings = [
        'max' => [
            'text'   => 'max',
            'number' => 'max_value',
        ],

        'min' => [
            'text'   => 'min',
            'number' => 'min_value',
        ],
    ];

    
    public function __construct(
        public string $item_key,
        public string $na,
        public string $title,
        public ?string $info,
        public string $type,
        public ?string $path,
        public ?string $validation,
        public ?string $depends,
        public ?string $default,
        public ?bool $channel_based,
        public ?bool $locale_based,
        public array|string $options,
        public bool $is_visible = true,
    ) {
        $this->options = $this->getOptions();
    }

    
    public function getName(): ?string
    {
        return $this->name;
    }

    
    public function getInfo(): ?string
    {
        return $this->info ?? '';
    }

    
    public function getTitle(): ?string
    {
        return $this->title ?? '';
    }

    
    public function getType(): string
    {
        return $this->type;
    }

    
    public function getPath(): ?string
    {
        return $this->path;
    }

    
    public function getItemKey(): string
    {
        return $this->item_key;
    }

    
    public function getValidations(): ?string
    {
        if (empty($this->validation)) {
            return '';
        }

        foreach ($this->veeValidateMappings as $laravelRule => $veeValidateRule) {
            if (! array_key_exists($this->getType(), $veeValidateRule)) {
                continue;
            }

            $this->validation = str_replace($laravelRule, $veeValidateRule[$this->getType()], $this->validation);
        }

        return $this->validation;
    }

    
    public function getDepends(): ?string
    {
        return $this->depends;
    }

    
    public function getDefault(): ?string
    {
        return $this->default;
    }

    
    public function getChannelBased(): ?bool
    {
        return $this->channel_based;
    }

    
    public function getLocaleBased(): ?bool
    {
        return $this->locale_based;
    }

    
    public function getNameKey(): string
    {
        return $this->item_key.'.'.$this->name;
    }

    
    public function isRequired(): string
    {
        return Str::contains($this->getValidations(), 'required') ? 'required' : '';
    }

    
    public function getOptions(): array
    {
        if (is_array($this->options)) {
            return collect($this->options)->map(fn ($option) => [
                'title' => trans($option['title']),
                'value' => $option['value'],
            ])->toArray();
        }

        return collect($this->getFieldOptions($this->options))->map(fn ($option) => [
            'title' => trans($option['title']),
            'value' => $option['value'],
        ])->toArray();
    }

    
    public function toArray()
    {
        return [
            'name'          => $this->getName(),
            'title'         => $this->getTitle(),
            'info'          => $this->getInfo(),
            'type'          => $this->getType(),
            'path'          => $this->getPath(),
            'depends'       => $this->getDepends(),
            'validation'    => $this->getValidations(),
            'default'       => $this->getDefault(),
            'channel_based' => $this->getChannelBased(),
            'locale_based'  => $this->getLocaleBased(),
            'options'       => $this->getOptions(),
            'item_key'      => $this->getItemKey(),
        ];
    }

    
    public function getNameField($key = null)
    {
        if (! $key) {
            $key = $this->item_key.'.'.$this->name;
        }

        $nameField = '';

        foreach (explode('.', $key) as $key => $field) {
            $nameField .= $key === 0 ? $field : '['.$field.']';
        }

        return $nameField;
    }

    
    public function getDependFieldName(): string
    {
        if (empty($depends = $this->getDepends())) {
            return '';
        }

        $dependNameKey = $this->getItemKey().'.'.collect(explode(':', $depends))->first();

        return $this->getNameField($dependNameKey);
    }

    
    protected function getFieldOptions(string $options): array
    {
        [$class, $method] = Str::parseCallback($options);

        return app($class)->$method();
    }
}
