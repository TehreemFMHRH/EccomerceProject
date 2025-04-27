<?php

namespace Webkul\Admin\Http\Controllers\User;

use Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Http\Controllers\Controller;

class AccountController extends Controller
{
    
    public function edit()
    {
        $user = auth()->guard('admin')->user();

        return view('admin::account.edit', compact('user'));
    }

    
    public function update()
    {
        $user = auth()->guard('admin')->user();

        $this->validate(request(), [
            'name'             => 'required',
            'email'            => 'email|unique:admins,email,'.$user->id,
            'password'         => 'nullable|min:6|confirmed',
            'current_password' => 'required|min:6',
            'image.*'          => 'nullable|mimes:bmp,jpeg,jpg,png,webp',
        ]);

        $dat = request()->only([
            'name',
            'email',
            'password',
            'password_confirmation',
            'current_password',
            'image',
        ]);

        if (! Hash::check($dat['current_password'], $user->password)) {
            session()->flash('warning', trans('admin::app.account.edit.invalid-password'));

            return redirect()->back();
        }

        $isPasswordChanged = false;

        if (! $dat['password']) {
            unset($dat['password']);
        } else {
            $isPasswordChanged = true;

            $dat['password'] = bcrypt($dat['password']);
        }

        if (request()->hasFile('image')) {
            $dat['image'] = current(request()->file('image'))->store('admins/'.$user->id);
        } else {
            if (! isset($dat['image'])) {
                if (! empty($dat['image'])) {
                    Storage::delete($user->image);
                }

                $dat['image'] = null;
            } else {
                $dat['image'] = $user->image;
            }
        }

        $user->update($dat);

        if ($isPasswordChanged) {
            Event::dispatch('admin.password.update.after', $user);
        }

        session()->flash('success', trans('admin::app.account.edit.update-success'));

        return back();
    }
}
