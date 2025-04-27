<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Webkul\Admin\Http\Controllers\Controller;

class ResetPasswordController extends Controller
{
    use ResetsPasswords;

    
    public function create($token = null)
    {
        return view('admin::users.reset-password.create')->with([
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
                request(['email', 'password', 'password_confirmation', 'token']), function ($admin, $password) {
                    $this->resetPassword($admin, $password);
                }
            );

            if ($resp == Password::PASSWORD_RESET) {
                return redirect()->route('admin.dashboard.index');
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

    
    protected function resetPassword($admin, $password)
    {
        $admin->password = Hash::make($password);

        $admin->setRememberToken(Str::random(60));

        $admin->save();

        event(new PasswordReset($admin));

        auth()->guard('admin')->login($admin);
    }

    
    public function broker()
    {
        return Password::broker('admins');
    }
}
