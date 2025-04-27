<?php

namespace Webkul\Installer\Database\Seeders\SocialLogin;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    
    public function run($parameters = [])
    {
        $this->call(CustomerSocialAccountTableSeeder::class, false, ['parameters' => $parameters]);
    }
}
