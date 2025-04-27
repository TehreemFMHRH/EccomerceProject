<?php

namespace Webkul\DataGrid;


class MassAction
{
    
    public function __construct(
        public string $icon,
        public string $title,
        public string $method,
        public mixed $url,
        public array $options = [],
    ) {}

    
    public function toArray()
    {
        return [
            'icon'    => $this->icon,
            'title'   => $this->title,
            'method'  => $this->method,
            'url'     => $this->url,
            'options' => $this->options,
        ];
    }
}
