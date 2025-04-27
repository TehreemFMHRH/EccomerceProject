<?php

namespace Webkul\Shop\Http\Controllers\Customer;

use Cookie;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Webkul\Core\Repositories\SubscribersListRepository;
use Webkul\Customer\Repositories\CustomerGroupRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Shop\Http\Controllers\Controller;
use Webkul\Shop\Http\Requests\Customer\RegistrationRequest;
use Webkul\Shop\Mail\Customer\EmailVerificationNotification;
use Webkul\Shop\Mail\Customer\RegistrationNotification;

class RegistrationController extends Controller
{
    
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected CustomerGroupRepository $customerGroupRepository,
        protected SubscribersListRepository $subscriptionRepository
    ) {}

    
    public function index()
    {
        return view('shop::customers.sign-up');
    }

    
    public function store(RegistrationRequest $registrationRequest)
    {
        $customerGroup = core()->getConfigData('customer.settings.create_new_account_options.default_group');

        $dat = array_merge($registrationRequest->only([
            'first_name',
            'last_name',
            'email',
            'password_confirmation',
            'is_subscribed',
        ]), [
            'password'                  => bcrypt(request()->input('password')),
            'api_token'                 => Str::random(80),
            'is_verified'               => ! core()->getConfigData('customer.settings.email.verification'),
            'customer_group_id'         => $this->customerGroupRepository->findOneWhere(['code' => $customerGroup])->id,
            'channel_id'                => core()->getCurrentChannel()->id,
            'token'                     => md5(uniqid(rand(), true)),
            'subscribed_to_news_letter' => (bool) request()->input('is_subscribed'),
        ]);

        Event::dispatch('customer.registration.before');

        $k = $this->customerRepository->create($dat);

        if (isset($dat['is_subscribed'])) {
            $subscription = $this->subscriptionRepository->findOneWhere(['email' => $dat['email']]);

            if ($subscription) {
                $this->subscriptionRepository->update([
                    'customer_id' => $k->id,
                ], $subscription->id);
            } else {
                Event::dispatch('customer.subscription.before');

                $subscription = $this->subscriptionRepository->create([
                    'email'         => $dat['email'],
                    'customer_id'   => $k->id,
                    'channel_id'    => core()->getCurrentChannel()->id,
                    'is_subscribed' => 1,
                    'token'         => uniqid(),
                ]);

                Event::dispatch('customer.subscription.after', $subscription);
            }
        }

        Event::dispatch('customer.create.after', $k);

        Event::dispatch('customer.registration.after', $k);

        if (core()->getConfigData('emails.general.notifications.emails.general.notifications.verification')) {
            session()->flash('success', trans('shop::app.customers.signup-form.success-verify'));
        } else {
            session()->flash('success', trans('shop::app.customers.signup-form.success'));
        }

        return redirect()->route('shop.customer.session.index');
    }

    
    public function verifyAccount($token)
    {
        $k = $this->customerRepository->findOneByField('token', $token);

        if ($k) {
            $this->customerRepository->update([
                'is_verified' => 1,
                'token'       => null,
            ], $k->id);

            if ((bool) core()->getConfigData('emails.general.notifications.emails.general.notifications.registration')) {
                Mail::queue(new RegistrationNotification($k));
            }

            $this->customerRepository->syncNewRegisteredCustomerInformation($k);

            session()->flash('success', trans('shop::app.customers.signup-form.verified'));
        } else {
            session()->flash('warning', trans('shop::app.customers.signup-form.verify-failed'));
        }

        return redirect()->route('shop.customer.session.index');
    }

    
    public function resendVerificationEmail($e)
    {
        $verificationData = [
            'email' => $e,
            'token' => md5(uniqid(rand(), true)),
        ];

        $k = $this->customerRepository->findOneByField('email', $e);

        $this->customerRepository->update(['token' => $verificationData['token']], $k->id);

        try {
            Mail::queue(new EmailVerificationNotification($verificationData));

            if (Cookie::has('enable-resend')) {
                \Cookie::queue(\Cookie::forget('enable-resend'));
            }

            if (Cookie::has('email-for-resend')) {
                \Cookie::queue(\Cookie::forget('email-for-resend'));
            }
        } catch (\Exception $e) {
            report($e);

            session()->flash('error', trans('shop::app.customers.signup-form.verification-not-sent'));

            return redirect()->back();
        }

        session()->flash('success', trans('shop::app.customers.signup-form.verification-sent'));

        return redirect()->back();
    }
}
