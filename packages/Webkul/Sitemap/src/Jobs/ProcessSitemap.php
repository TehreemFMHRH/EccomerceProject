<?php

namespace Webkul\Sitemap\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;
use Webkul\Sitemap\Contracts\Sitemap as SitemapContract;
use Webkul\Sitemap\Models\Category;
use Webkul\Sitemap\Models\Page;
use Webkul\Sitemap\Models\Product;

class ProcessSitemap implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    
    protected int $batchProcessed = 0;

    
    protected array $itemsToBeProcessed = [];

    
    protected array $generatedSitemaps = [];

    
    public function __construct(
        public SitemapContract $sitemap
    ) {}

    
    public function handle(): void
    {
        
        if (! core()->getConfigData('general.sitemap.settings.enabled')) {
            return;
        }

        
        $this->sitemap->deleteFromStorage();

        
        $this->processItems([Url::create('/')]);

        
        Category::query()->chunk(100, fn ($items) => $this->processItems($items));

        
        Product::query()->chunk(100, fn ($items) => $this->processItems($items));

        
        Page::query()->chunk(100, fn ($items) => $this->processItems($items));

        
        if (! empty($this->itemsToBeProcessed)) {
            $this->generateSitemap();
        }

        
        $this->generateSitemapIndex();

        
        $this->sitemap->update([
            'generated_at' => now(),

            'additional'   => array_merge($this->sitemap->additional ?? [], [
                'index'    => $this->sitemap->index_file_name,
                'sitemaps' => $this->generatedSitemaps,
            ]),
        ]);
    }

    
    protected function processItems($items): void
    {
        foreach ($items as $item) {
            $this->itemsToBeProcessed[] = $item;

            if (count($this->itemsToBeProcessed) === (int) core()->getConfigData('general.sitemap.file_limits.max_url_per_file')) {
                $this->generateSitemap();
            }
        }
    }

    
    protected function generateSitemap(): void
    {
        $this->batchProcessed++;

        $sitemap = Sitemap::create();

        foreach ($this->itemsToBeProcessed as $item) {
            $sitemap->add($item);
        }

        $sitemapFilePath = clean_path($this->sitemap->path.'/'.File::name($this->sitemap->file_name).'-'.$this->sitemap->id.'-'.$this->batchProcessed.'.'.File::extension($this->sitemap->file_name));

        $sitemap->writeToDisk('public', $sitemapFilePath);

        $this->generatedSitemaps[] = $sitemapFilePath;

        $this->itemsToBeProcessed = [];
    }

    
    protected function generateSitemapIndex(): void
    {
        $sitemap = SitemapIndex::create();

        foreach ($this->generatedSitemaps as $generatedSitemap) {
            $sitemap->add(Storage::url($generatedSitemap));
        }

        $sitemap->writeToDisk('public', $this->sitemap->index_file_name);
    }
}
