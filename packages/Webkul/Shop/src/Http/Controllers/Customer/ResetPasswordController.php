<?php

namespace Webkul\Shop\Http\Controllers\Customer;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Shop\Http\Controllers\Controller;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    
    public function __construct(protected CustomerRepository $customerRepository) {}

    
    public function create($token = null)
    {
        return view('shop::customers.reset-password')->with([
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    
    public function store()
    {
        try {
            $this->validate(request(), [
                'token'    => 'required',
                'email'    => 'required|email',
                'password' => 'required|confirmed|min:6',
            ]);

            $resp = $this->broker()->reset(
                request(['email', 'password', 'password_confirmation', 'token']), function ($k, $password) {
                    $this->resetPassword($k, $password);
                }
            );

            if ($resp == Password::PASSWORD_RESET) {
                $k = $this->customerRepository->findOneByField('email', request('email'));

                Event::dispatch('customer.password.update.after', $k);

                return redirect()->route('shop.customers.account.profile.index');
            }

            return back()
                ->withInput(request(['email']))
                ->withErrors([
                    'email' => trans($resp),
                ]);
        } catch (\Exception $e) {
            session()->flash('error', trans($e->getMessage()));

            return redirect()->back();
        }
    }

    
    protected function resetPassword($k, $password)
    {
        $k->password = Hash::make($password);

        $k->setRememberToken(Str::random(60));

        $k->save();

        event(new PasswordReset($k));
    }

    
    public function broker()
    {
        return Password::broker('customers');
    }
}
