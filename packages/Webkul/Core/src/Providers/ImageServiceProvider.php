<?php

namespace Webkul\Core\Providers;

use Intervention\Image\ImageManager;
use Intervention\Image\ImageServiceProvider as BaseImageServiceProvider;


class ImageServiceProvider extends BaseImageServiceProvider
{
    
    public function register()
    {
        $this->app->singleton('image', function ($app) {
            return new ImageManager($this->getImageConfig($app));
        });

        $this->app->alias('image', 'Intervention\Image\ImageManager');
    }

    
    public function boot()
    {
        $this->cacheIsInstalled()
            ? $this->bootstrapImageCache()
            : null;
    }

    
    public function provides()
    {
        return ['image'];
    }

    
    protected function bootstrapImageCache()
    {
        
        if (is_string(config('imagecache.route'))) {
            $filenamePattern = '[ \w\\.\\/\\-\\@\(\)\=]+';

            $this->app['router']->get(config('imagecache.route').'/{template}/{filename}', [
                'uses' => 'Webkul\Core\ImageCache\Controller@getResponse',
                'as'   => 'imagecache',
            ])->where(['filename' => $filenamePattern]);
        }
    }

    
    private function cacheIsInstalled()
    {
        return class_exists('Intervention\\Image\\ImageCache');
    }

    
    private function getImageConfig($app)
    {
        $config = $app['config']->get('image');

        if (is_null($config)) {
            return [];
        }

        return $config;
    }
}
