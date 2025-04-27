<?php

use Webkul\Core\Facades\Acl;
use Webkul\Core\Facades\Core;
use Webkul\Core\Facades\Menu;
use Webkul\Core\Facades\SystemConfig;

if (! function_exists('core')) {
    
    function core()
    {
        return Core::getFacadeRoot();
    }
}

if (! function_exists('menu')) {
    
    function menu()
    {
        return Menu::getFacadeRoot();
    }
}

if (! function_exists('acl')) {
    
    function acl()
    {
        return Acl::getFacadeRoot();
    }
}

if (! function_exists('system_config')) {
    
    function system_config()
    {
        return SystemConfig::getFacadeRoot();
    }
}

if (! function_exists('clean_path')) {
    
    function clean_path(string $path): string
    {
        return collect(explode('/', $path))
            ->filter(fn ($segment) => ! empty($segment))
            ->join('/');
    }
}

if (! function_exists('array_permutation')) {
    function array_permutation($input)
    {
        $results = [];

        foreach ($input as $key => $values) {
            if (empty($values)) {
                continue;
            }

            if (empty($results)) {
                foreach ($values as $va) {
                    $results[] = [$key => $va];
                }
            } else {
                $append = [];

                foreach ($results as &res) {
                    res[$key] = array_shift($values);

                    $copy = res;

                    foreach ($values as $item) {
                        $copy[$key] = $item;
                        $append[] = $copy;
                    }

                    array_unshift($values, res[$key]);
                }

                $results = array_merge($results, $append);
            }
        }

        return $results;
    }
}
