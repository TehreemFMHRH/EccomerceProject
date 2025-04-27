<?php

namespace Webkul\Installer\Database\Seeders\User;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    
    public function run($parameters = [])
    {
        $this->call(RolesTableSeeder::class, false, ['parameters' => $parameters]);
        $this->call(AdminsTableSeeder::class, false, ['parameters' => $parameters]);
    }
}
