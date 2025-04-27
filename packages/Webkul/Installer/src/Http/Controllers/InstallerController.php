<?php

namespace Webkul\Installer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Webkul\Installer\Helpers\DatabaseManager;
use Webkul\Installer\Helpers\EnvironmentManager;
use Webkul\Installer\Helpers\ServerRequirements;

class InstallerController extends Controller
{
    
    const MIN_PHP_VERSION = '8.1.0';

    
    const USER_ID = 1;

    
    public function __construct(
        protected ServerRequirements $serverRequirements,
        protected EnvironmentManager $environmentManager,
        protected DatabaseManager $databaseManager
    ) {}

    
    public function index()
    {
        $phpVersion = $this->serverRequirements->checkPHPversion(self::MIN_PHP_VERSION);

        $requirements = $this->serverRequirements->validate();

        if (request()->has('locale')) {
            return redirect()->route('installer.index');
        }

        return view('installer::installer.index', compact('requirements', 'phpVersion'));
    }

    
    public function envFileSetup(Request $request): JsonResponse
    {
        $message = $this->environmentManager->generateEnv($request);

        return new JsonResponse(['data' => $message]);
    }

    
    public function runMigration()
    {
        $migration = $this->databaseManager->migration();

        return $migration;
    }

    
    public function runSeeder()
    {
        $selectedParameters = request()->selectedParameters;
        $allParameters = request()->allParameters;

        $appLocale = $allParameters['app_locale'] ?? null;
        $appCurrency = $allParameters['app_currency'] ?? null;

        $allowedLocales = array_unique(
            array_merge(
                [($appLocale ?? 'en')],
                $selectedParameters['allowed_locales']
            )
        );

        $allowedCurrencies = array_unique(
            array_merge(
                [($appCurrency ?? 'USD')],
                $selectedParameters['allowed_currencies']
            )
        );

        $parameter = [
            'parameter' => [
                'default_locales'    => $appLocale,
                'default_currency'   => $appCurrency,
                'allowed_locales'    => $allowedLocales,
                'allowed_currencies' => $allowedCurrencies,
            ],
        ];

        $resp = $this->environmentManager->setEnvConfiguration(request()->allParameters);

        if ($resp) {
            $seeder = $this->databaseManager->seeder($parameter);

            return $seeder;
        }
    }

    
    public function createSampleProducts()
    {
        $defaultLocale = config('app.locale');

        $allowedLocales = array_merge([$defaultLocale], request()->input('selectedLocales'));

        $defaultCurrency = config('app.currency');

        $allowedCurrencies = array_merge([$defaultCurrency], request()->input('selectedCurrencies'));

        $this->databaseManager->seedSampleProducts([
            'default_locale'     => $defaultLocale,
            'allowed_locales'    => $allowedLocales,
            'default_currency'   => $defaultCurrency,
            'allowed_currencies' => $allowedCurrencies,
        ]);
    }

    
    public function adminConfigSetup()
    {
        $password = password_hash(request()->input('password'), PASSWORD_BCRYPT, ['cost' => 10]);

        try {
            DB::table('admins')->updateOrInsert(
                [
                    'id' => self::USER_ID,
                ], [
                    'name'     => request()->input('admin'),
                    'email'    => request()->input('email'),
                    'password' => $password,
                    'role_id'  => 1,
                    'status'   => 1,
                ]
            );

            return true;
        } catch (\Throwable $th) {
            Log::error('Error in Admin installer config setup'.$th->getMessage());

            return false;
        }
    }

    
    public function smtpConfigSetup()
    {
        $this->environmentManager->setEnvConfiguration(request()->input());

        $filePath = storage_path('installed');

        File::put($filePath, 'Your Bagisto App is Successfully Installed');

        Event::dispatch('bagisto.installed');

        return $filePath;
    }
}
