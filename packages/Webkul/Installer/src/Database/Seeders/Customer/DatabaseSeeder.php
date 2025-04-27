<?php

namespace Webkul\Installer\Database\Seeders\Customer;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    
    public function run($parameters = [])
    {
        $this->call(CustomerGroupTableSeeder::class, false, ['parameters' => $parameters]);
    }
}
