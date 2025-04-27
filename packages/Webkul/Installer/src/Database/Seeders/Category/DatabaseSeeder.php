<?php

namespace Webkul\Installer\Database\Seeders\Category;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    
    public function run($parameters = [])
    {
        $this->call(CategoryTableSeeder::class, false, ['parameters' => $parameters]);
    }
}
