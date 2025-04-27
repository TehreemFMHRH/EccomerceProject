<?php

namespace Webkul\Theme;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Webkul\Theme\Exceptions\ViterNotFound;

class Themes
{
    
    protected $activeTheme = null;

    
    protected $themes = [];

    
    protected $laravelViewsPath;

    
    public function __construct()
    {
        $this->laravelViewsPath = Config::get('view.paths');

        $this->loadThemes();
    }

    
    public function all()
    {
        return $this->themes;
    }

    
    public function getChannelThemes()
    {
        $themes = config('themes.shop', []);

        $channelThemes = [];

        foreach ($themes as $code => $dat) {
            $channelThemes[] = new Theme(
                $code,
                $dat['name'] ?? '',
                $dat['assets_path'] ?? '',
                $dat['views_path'] ?? '',
                isset($dat['vite']) ? $dat['vite'] : [],
            );

            if (! empty($dat['parent'])) {
                $parentThemes[$code] = $dat['parent'];
            }
        }

        return $channelThemes;
    }

    
    public function exists(string $themeName)
    {
        foreach ($this->themes as $theme) {
            if ($theme->code == $themeName) {
                return true;
            }
        }

        return false;
    }

    
    public function loadThemes()
    {
        $parentThemes = [];

        if (Str::contains(request()->url(), config('app.admin_url').'/')) {
            $themes = config('themes.admin', []);
        } else {
            $themes = config('themes.shop', []);
        }

        foreach ($themes as $code => $dat) {
            $this->themes[] = new Theme(
                $code,
                $dat['name'] ?? '',
                $dat['assets_path'] ?? '',
                $dat['views_path'] ?? '',
                $dat['vite'] ?? [],
            );

            if (! empty($dat['parent'])) {
                $parentThemes[$code] = $dat['parent'];
            }
        }

        foreach ($parentThemes as $childCode => $parentCode) {
            $child = $this->find($childCode);

            if ($this->exists($parentCode)) {
                $parent = $this->find($parentCode);
            } else {
                $parent = new Theme($parentCode);
            }

            $child->setParent($parent);
        }
    }

    
    public function set(string $themeName)
    {
        if ($this->exists($themeName)) {
            $theme = $this->find($themeName);
        } else {
            $theme = new Theme($themeName);
        }

        $this->activeTheme = $theme;

        $paths = $theme->getViewPaths();

        foreach ($this->laravelViewsPath as $path) {
            if (! in_array($path, $paths)) {
                $paths[] = $path;
            }
        }

        Config::set('view.paths', $paths);

        $themeViewFinder = app('view.finder');

        $themeViewFinder->setPaths($paths);

        return $theme;
    }

    
    public function current()
    {
        return $this->activeTheme ?? null;
    }

    
    public function getName()
    {
        return $this->current()?->name ?? '';
    }

    
    public function find(string $themeName)
    {
        foreach ($this->themes as $theme) {
            if ($theme->code == $themeName) {
                return $theme;
            }
        }

        throw new Exceptions\ThemeNotFound($themeName);
    }

    
    public function getLaravelViewPaths()
    {
        return $this->laravelViewsPath;
    }

    
    public function url(string $filename, ?string $namespace = null)
    {
        $url = trim($filename, '/');

        
        if (empty($namespace)) {
            return $this->current()->url($url);
        }

        
        $viters = config('bagisto-vite.viters');

        if (empty($viters[$namespace])) {
            throw new ViterNotFound($namespace);
        }

        $viteUrl = trim($viters[$namespace]['package_assets_directory'], '/').'/'.$url;

        return Vite::useHotFile($viters[$namespace]['hot_file'])
            ->useBuildDirectory($viters[$namespace]['build_directory'])
            ->asset($viteUrl);
    }

    
    public function setBagistoVite($entryPoints, ?string $namespace = null)
    {
        
        if (empty($namespace)) {
            return $this->current()->setBagistoVite($entryPoints);
        }

        
        $viters = config('bagisto-vite.viters');

        if (empty($viters[$namespace])) {
            throw new ViterNotFound($namespace);
        }

        return Vite::useHotFile($viters[$namespace]['hot_file'])
            ->useBuildDirectory($viters[$namespace]['build_directory'])
            ->withEntryPoints($entryPoints);
    }
}
