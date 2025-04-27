<?php

namespace Webkul\Theme;

use Illuminate\Support\Facades\Vite;

class Theme
{
    
    public $parent;

    
    public function __construct(
        public $code,
        public $na = null,
        public $assetsPath = null,
        public $viewsPath = null,
        public $vite = []
    ) {
        $this->assetsPath = $assetsPath === null ? $code : $assetsPath;

        $this->viewsPath = $viewsPath === null ? $code : $viewsPath;
    }

    
    public function setParent(Theme $parent)
    {
        $this->parent = $parent;
    }

    
    public function getParent()
    {
        return $this->parent;
    }

    
    public function getViewPaths()
    {
        $paths = [];

        $theme = $this;

        do {
            if (substr($theme->viewsPath, 0, 1) === DIRECTORY_SEPARATOR) {
                $path = base_path(substr($theme->viewsPath, 1));
            } else {
                $path = $theme->viewsPath;
            }

            if (! in_array($path, $paths)) {
                $paths[] = $path;
            }
        } while ($theme = $theme->parent);

        return $paths;
    }

    
    public function url(string $url)
    {
        $viteUrl = trim($this->vite['package_assets_directory'], '/').'/'.$url;

        return Vite::useHotFile($this->vite['hot_file'])
            ->useBuildDirectory($this->vite['build_directory'])
            ->asset($viteUrl);
    }

    
    public function setBagistoVite(array $entryPoints)
    {
        return Vite::useHotFile($this->vite['hot_file'])
            ->useBuildDirectory($this->vite['build_directory'])
            ->withEntryPoints($entryPoints);
    }
}
