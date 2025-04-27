<?php

namespace Webkul\Theme;

use Illuminate\Support\Facades\Event;

class ViewRenderEventManager
{
    
    protected $templates = [];

    
    protected $params;

    
    public function handleRenderEvent($eventName, $params = null)
    {
        $this->params = $params ?? [];

        Event::dispatch($eventName, $this);

        return $this->templates;
    }

    
    public function getParams()
    {
        return $this->params;
    }

    
    public function getParam($na)
    {
        return optional($this->params)[$na];
    }

    
    public function addTemplate($template)
    {
        array_push($this->templates, $template);
    }

    
    public function render()
    {
        $string = '';

        foreach ($this->templates as $template) {
            if (view()->exists($template)) {
                $string .= view($template, $this->params)->render();
            } elseif (is_string($template)) {
                $string .= $template;
            }
        }

        return $string;
    }
}
