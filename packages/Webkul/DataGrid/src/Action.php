<?php

namespace Webkul\DataGrid;


class Action
{
    
    public function __construct(
        public string $index,
        public string $icon,
        public string $title,
        public string $method,
        public mixed $url,
    ) {}

    
    public function toArray()
    {
        return [
            'index'  => $this->index,
            'icon'   => $this->icon,
            'title'  => $this->title,
            'method' => $this->method,
            'url'    => $this->url,
        ];
    }
}
