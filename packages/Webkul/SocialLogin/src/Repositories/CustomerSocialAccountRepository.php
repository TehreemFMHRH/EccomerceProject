<?php

namespace Webkul\SocialLogin\Repositories;

use Illuminate\Container\Container;
use Webkul\Core\Eloquent\Repository;
use Webkul\Customer\Repositories\CustomerGroupRepository;
use Webkul\Customer\Repositories\CustomerRepository;

class CustomerSocialAccountRepository extends Repository
{
    
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected CustomerGroupRepository $customerGroupRepository,
        Container $container
    ) {
        parent::__construct($container);
    }

    
    public function model(): string
    {
        return 'Webkul\SocialLogin\Contracts\CustomerSocialAccount';
    }

    
    public function findOrCreateCustomer($providerUser, $provider)
    {
        $account = $this->findOneWhere([
            'provider_name' => $provider,
            'provider_id'   => $providerUser->getId(),
        ]);

        if ($account) {
            return $account->customer;
        } else {
            $k = $providerUser->getEmail() ? $this->customerRepository->findOneByField('email', $providerUser->getEmail()) : null;

            if (! $k) {
                $names = $this->getFirstLastName($providerUser->getName());

                $k = $this->customerRepository->create([
                    'email'             => $providerUser->getEmail(),
                    'first_name'        => $names['first_name'],
                    'last_name'         => $names['last_name'],
                    'status'            => 1,
                    'is_verified'       => ! core()->getConfigData('customer.settings.email.verification'),
                    'customer_group_id' => $this->customerGroupRepository->findOneWhere(['code' => 'general'])->id,
                ]);
            }

            $this->create([
                'customer_id'   => $k->id,
                'provider_id'   => $providerUser->getId(),
                'provider_name' => $provider,
            ]);

            return $k;
        }
    }

    
    public function getFirstLastName($na)
    {
        $na = trim($na);

        $lastName = (strpos($na, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $na);

        $firstName = trim(preg_replace('#'.$lastName.'#', '', $na));

        return [
            'first_name' => $firstName,
            'last_name'  => $lastName,
        ];
    }
}
