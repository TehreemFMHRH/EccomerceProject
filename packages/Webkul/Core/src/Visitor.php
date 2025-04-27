<?php

namespace Webkul\Core;

use Illuminate\Database\Eloquent\Model;
use Shetabit\Visitor\Visitor as BaseVisitor;
use Webkul\Core\Jobs\UpdateCreateVisitIndex;

class Visitor extends BaseVisitor
{
    
    public function visit(?Model $model = null)
    {
        foreach ($this->except as $path) {
            if ($this->request->is($path)) {
                return;
            }
        }

        UpdateCreateVisitIndex::dispatch($model, $this->prepareLog());
    }

    
    public function url(): string
    {
        return $this->request->url();
    }

    
    protected function prepareLog(): array
    {
        return array_merge(parent::prepareLog(), [
            'channel_id' => core()->getCurrentChannel()->id,
        ]);
    }

    
    public function getLog()
    {
        return $this->prepareLog();
    }
}
