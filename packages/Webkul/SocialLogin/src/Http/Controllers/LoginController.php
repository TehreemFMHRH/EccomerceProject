<?php

namespace Webkul\SocialLogin\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Event;
use Laravel\Socialite\Facades\Socialite;
use Webkul\SocialLogin\Repositories\CustomerSocialAccountRepository;

class LoginController extends Controller
{
    use DispatchesJobs, ValidatesRequests;

    
    public function __construct(protected CustomerSocialAccountRepository $customerSocialAccountRepository) {}

    
    public function redirectToProvider($provider)
    {
        try {
            return Socialite::driver($provider)->redirect('shop.customers.account.profile.index');
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());

            return redirect()->route('shop.customer.session.index');
        }
    }

    
    public function handleProviderCallback($provider)
    {
        try {
            $user = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('shop.customer.session.index');
        }

        $k = $this->customerSocialAccountRepository->findOrCreateCustomer($user, $provider);

        auth()->guard('customer')->login($k, true);

        Event::dispatch('customer.after.login', $k);

        return redirect()->intended(route('shop.customers.account.profile.index'));
    }
}
