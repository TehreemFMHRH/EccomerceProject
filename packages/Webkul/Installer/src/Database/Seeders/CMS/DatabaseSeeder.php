<?php

namespace Webkul\Installer\Database\Seeders\CMS;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    
    public function run($parameters = [])
    {
        $this->call(CMSPagesTableSeeder::class, false, ['parameters' => $parameters]);
    }
}
