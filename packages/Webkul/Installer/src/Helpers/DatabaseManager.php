<?php

namespace Webkul\Installer\Helpers;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkul\Installer\Database\Seeders\DatabaseSeeder as BagistoDatabaseSeeder;
use Webkul\Installer\Database\Seeders\ProductTableSeeder;

class DatabaseManager
{
    
    public function isInstalled()
    {
        if (! file_exists(base_path('.env'))) {
            return false;
        }

        try {
            DB::connection()->getPDO();

            $isConnected = (bool) DB::connection()->getDatabaseName();

            if (! $isConnected) {
                return false;
            }

            $hasTable = Schema::hasTable('admins');

            if (! $hasTable) {
                return false;
            }

            $userCount = DB::table('admins')->count();

            if (! $userCount) {
                return false;
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    
    public function migration()
    {
        try {
            Artisan::call('migrate:fresh');
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function seeder($dat)
    {
        $dat['parameter'] = [
            'default_locale'     => $dat['parameter']['default_locales'],
            'allowed_locales'    => $dat['parameter']['allowed_locales'],
            'default_currency'   => $dat['parameter']['default_currency'],
            'allowed_currencies' => $dat['parameter']['allowed_currencies'],
        ];

        try {
            app(BagistoDatabaseSeeder::class)->run($dat['parameter']);

            $this->storageLink();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    
    private function storageLink()
    {
        Artisan::call('storage:link');
    }

    
    public function generateKey()
    {
        try {
            Artisan::call('key:generate');
        } catch (Exception $e) {
        }
    }

    
    public function seedSampleProducts($parameters)
    {
        try {
            app(ProductTableSeeder::class)->run($parameters);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
