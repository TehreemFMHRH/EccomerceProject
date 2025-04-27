<?php

namespace Webkul\Core;

use Konekt\Concord\Conventions\ConcordDefault;

class CoreConvention extends ConcordDefault
{
    
    public function migrationsFolder(): string
    {
        return 'Database/Migrations';
    }

    
    public function manifestFile(): string
    {
        return 'Resources/manifest.php';
    }
}
