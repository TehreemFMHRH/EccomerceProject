<?php

namespace Webkul\Sitemap\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Sitemap\Contracts\Sitemap;

class SitemapRepository extends Repository
{
    
    public function model(): string
    {
        return Sitemap::class;
    }
}
