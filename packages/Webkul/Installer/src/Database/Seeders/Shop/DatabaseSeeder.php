<?php

namespace Webkul\Installer\Database\Seeders\Shop;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    
    public function run($parameters = [])
    {
        $this->call(ThemeCustomizationTableSeeder::class, false, ['parameters' => $parameters]);
    }
}
