<?php

namespace Webkul\Shop\Http\Controllers\Customer;

use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;
use Webkul\Shop\Http\Controllers\Controller;
use Webkul\Shop\Http\Requests\Customer\ForgotPasswordRequest;

class ForgotPasswordController extends Controller
{
    use SendsPasswordResetEmails;

    
    public function create()
    {
        return view('shop::customers.forgot-password');
    }

    
    public function store(ForgotPasswordRequest $request)
    {
        $request->validated();

        try {
            $resp = $this->broker()->sendResetLink($request->only(['email']));

            if ($resp == Password::RESET_LINK_SENT) {
                session()->flash('success', trans('shop::app.customers.forgot-password.reset-link-sent'));

                return redirect()->route('shop.customers.forgot_password.create');
            }

            if ($resp == Password::RESET_THROTTLED) {
                session()->flash('warning', trans('shop::app.customers.forgot-password.already-sent'));

                return redirect()->route('shop.customers.forgot_password.create');
            }

            return redirect()->route('shop.customers.forgot_password.create')
                ->withInput($request->only(['email']))
                ->withErrors([
                    'email' => trans('shop::app.customers.forgot-password.email-not-exist'),
                ]);
        } catch (\Swift_RfcComplianceException $e) {
            session()->flash('success', trans('shop::app.customers.forgot-password.reset-link-sent'));

            return redirect()->route('shop.customers.forgot_password.create');
        } catch (\Exception $e) {
            report($e);

            session()->flash('error', $e->getMessage());

            return redirect()->route('shop.customers.forgot_password.create');
        }
    }

    
    public function broker()
    {
        return Password::broker('customers');
    }
}
