<?php

namespace Webkul\Installer\Listeners;

use GuzzleHttp\Client;
use Webkul\User\Repositories\AdminRepository;

class Installer
{
    
    protected const API_ENDPOINT = 'https://updates.bagisto.com/api/updates';

    
    public function __construct(protected AdminRepository $adminRepository) {}

    
    public function installed()
    {
        $admin = $this->adminRepository->first();

        $httpClient = new Client;

        try {
            $httpClient->request('POST', self::API_ENDPOINT, [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'json'    => [
                    'domain'       => config('app.url'),
                    'email'        => $admin?->email,
                    'name'         => $admin?->name,
                    'country_code' => config('app.default_country') ?? 'IN',
                ],
            ]);
        } catch (\Exception $e) {
            
        }
    }
}
