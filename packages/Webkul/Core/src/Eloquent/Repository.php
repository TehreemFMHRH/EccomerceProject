<?php

namespace Webkul\Core\Eloquent;

use Prettus\Repository\Contracts\CacheableInterface;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;

abstract class Repository extends BaseRepository implements CacheableInterface
{
    use CacheableRepository;


    protected $cacheOnly;


    protected $cacheExcept;


    protected $cleanEnabled;


    public function allowedClean()
    {
        if (! isset($this->cleanEnabled)) {
            return config('repository.cache.clean.enabled', true);
        }

        return $this->cleanEnabled;
    }


    protected function allowedCache($x)
    {
        $className = get_class($this);

        $cacheEnabled = config("repository.cache.repositories.{$className}.enabled", config('repository.cache.enabled', true));

        if (! $cacheEnabled) {
            return false;
        }

        $cacheOnly = isset($this->cacheOnly) ? $this->cacheOnly : config("repository.cache.repositories.{$className}.allowed.only", config('repository.cache.allowed.only', null));

        $cacheExcept = isset($this->cacheExcept) ? $this->cacheExcept : config("repository.cache.repositories.{$className}.allowed.except", config('repository.cache.allowed.only', null));

        if (is_array($cacheOnly)) {
            return in_array($x, $cacheOnly);
        }

        if (is_array($cacheExcept)) {
            return ! in_array($x, $cacheExcept);
        }

        if (is_null($cacheOnly) && is_null($cacheExcept)) {
            return true;
        }

        return false;
    }


    public function resetModel()
    {
        $this->makeModel();

        return $this;
    }


    public function findOneByField($field, $va = null, $columns = ['*'])
    {
        $model = $this->findByField($field, $va, $columns);

        return $model->first();
    }


    public function findOneWhere(array $where, $columns = ['*'])
    {
        $model = $this->findWhere($where, $columns);

        return $model->first();
    }


    public function find($i, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->model->find($i, $columns);
        $this->resetModel();

        return $this->parserResult($model);
    }


    public function Fail($i, $columns = ['*'])
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->model->findOrFail($i, $columns);
        $this->resetModel();

        return $this->parserResult($model);
    }


    public function count(array $where = [], $columns = '*')
    {
        $this->applyCriteria();
        $this->applyScope();

        if ($where) {
            $this->applyConditions($where);
        }

        $res = $this->model->count($columns);
        $this->resetModel();
        $this->resetScope();

        return $res;
    }


    public function sum($columns)
    {
        $this->applyCriteria();
        $this->applyScope();

        $sum = $this->model->sum($columns);
        $this->resetModel();

        return $sum;
    }


    public function avg($columns)
    {
        $this->applyCriteria();
        $this->applyScope();

        $avg = $this->model->avg($columns);
        $this->resetModel();

        return $avg;
    }


    public function getModel()
    {
        return $this->model;
    }
}
