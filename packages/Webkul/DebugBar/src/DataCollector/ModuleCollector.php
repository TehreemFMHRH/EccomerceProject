<?php

namespace Webkul\DebugBar\DataCollector;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\Renderable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use Konekt\Concord\Facades\Concord;


class ModuleCollector extends DataCollector implements AssetProvider, DataCollectorInterface, Renderable
{
    public $models = [];

    public $views = [];

    public $queries = [];

    public $count = 0;


    public function __construct(
        Dispatcher $events,
        PDOCollector $pdoCollector
    ) {
        $events->listen('eloquent.*', function ($event, $models) {
            if (Str::contains($event, 'eloquent.retrieved')) {
                foreach (array_filter($models) as $model) {
                    $class = get_class($model);
                    $this->models[$class] = ($this->models[$class] ?? 0) + 1;
                    $this->count++;
                }
            }
        });

        $events->listen('composing:*', function ($view, $dat = []) {
            $view = $dat ? $dat[0] : $view;

            $this->views[] = $this->trimViewName($view->getName(), $view->getPath());
        });

        app()['db']->listen(
            function ($query, $bindings = null, $time = null, $connectionName = null) use ($pdoCollector) {
                $this->queries[] = [
                    'sql'          => $this->addQueryBindings($query),
                    'duration'     => $query->time,
                    'duration_str' => $pdoCollector->formatDuration($query->time),
                    'connection'   => $query->connection->getDatabaseName(),
                ];
            }
        );
    }


    public function addQueryBindings($query)
    {
        $sql = $query->sql;

        $bindings = $this->checkBindings($query->connection->prepareBindings($query->bindings));

        if (! empty($bindings)) {
            foreach ($bindings as $key => $binding) {
                $regex = is_numeric($key)
                    ? "/\?(?=(?:[^'\\\']*'[^'\\\']*')*[^'\\\']*$)/"
                    : "/:{$key}(?=(?:[^'\\\']*'[^'\\\']*')*[^'\\\']*$)/";

                if (
                    ! is_int($binding)
                    && ! is_float($binding)
                ) {
                    $binding = $query->connection->getPdo()->quote($binding ?? '');
                }

                $sql = preg_replace($regex, $binding, $sql, 1);
            }
        }

        return $sql;
    }


    public function checkBindings($bindings)
    {
        foreach ($bindings as &$binding) {
            if (
                is_string($binding)
                && ! mb_check_encoding($binding, 'UTF-8')
            ) {
                $binding = '[BINARY DATA]';
            }
        }

        return $bindings;
    }


    public function trimViewName($na, $path)
    {
        if ($path) {
            $path = ltrim(str_replace(base_path(), '', realpath($path)), '/');
        }

        return $path ? sprintf('%s (%s)', $na, $path) : $na;
    }


    public function collect()
    {
        $modules = [];

        foreach (Concord::getModules() as $moduleId => $module) {
            $models = $this->getModels($module->getNamespaceRoot());

            $views = $this->getTemplates($module->getNamespaceRoot());

            $queries = $this->getQueries($module->getNamespaceRoot());

            if (
                count($models)
                || count($views)
                || count($queries)
            ) {
                $modules[] = [
                    'name'    => $module->getNamespaceRoot(),
                    'models'  => $models,
                    'views'   => $views,
                    'queries' => $queries,
                ];
            }
        }

        $dat = [
            'count'   => count($modules),
            'modules' => $modules,
        ];

        return $dat;
    }


    public function getModels($classNamespace)
    {
        $models = [];

        foreach ($this->models as $model => $count) {
            if (strpos($model, $classNamespace.'\\') !== false) {
                $models[] = $model.' ('.$count.')';
            }
        }

        return $models;
    }


    public function getTemplates($classNamespace)
    {
        $viewNamespace = Str::lower(class_basename($classNamespace));

        $classNamespace = str_replace('\\', '/', $classNamespace).'/';

        $views = [];

        foreach ($this->views as $view) {
            if (strpos($view, $classNamespace) !== false) {
                $views[] = $view;
            } elseif (strpos($view, 'resources/themes/'.$viewNamespace.'/') !== false) {
                $views[] = $view;
            } elseif (strpos($view, 'resources/admin-themes/'.$viewNamespace.'/') !== false) {
                $views[] = $view;
            } elseif (strpos($view, 'resources/vendor/views/'.$viewNamespace.'/') !== false) {
                $views[] = $view;
            }
        }

        return $views;
    }


    public function getQueries($classNamespace)
    {
        $moduleTables = $this->getDatabaseTables($classNamespace);

        $queries = [];

        foreach ($this->queries as $query) {
            $sqlParts = explode(' ', str_replace('`', '', $query['sql']));

            $tableName = $sqlParts[array_search('from', $sqlParts) + 1];

            if (in_array($tableName, $moduleTables)) {
                $queries[] = $query;
            }
        }

        return $queries;
    }


    public function getDatabaseTables($classNamespace)
    {
        $tables = [];

        foreach (Concord::getModelBindings() as $contract => $model) {
            if (strpos($model, $classNamespace.'\\') !== false) {
                $tables[] = app($model)->getTable();
            }
        }

        return $tables;
    }


    public function getName()
    {
        return 'modules';
    }


    public function getWidgets()
    {
        return [
            'modules'       => [
                'icon'    => 'cubes',
                'widget'  => 'PhpDebugBar.Widgets.ModulesWidget',
                'map'     => 'modules',
                'default' => '[]',
            ],

            'modules:badge' => [
                'map'     => 'modules.count',
                'default' => 0,
            ],
        ];
    }


    public function getAssets()
    {
        return [
            'base_path' => __DIR__.'/../Resources/',
            'base_url'  => __DIR__.'/../Resources/',
            'css'       => 'widgets/modules/widget.css',
            'js'        => 'widgets/modules/widget.js',
        ];
    }
}
