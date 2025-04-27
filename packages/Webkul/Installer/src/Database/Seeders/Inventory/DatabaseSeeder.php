<?php

namespace Webkul\Installer\Database\Seeders\Inventory;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    
    public function run($parameters = [])
    {
        $this->call(InventorySourceTableSeeder::class, false, ['parameters' => $parameters]);
    }
}
