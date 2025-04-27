<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Admin\DataGrids\Settings\UserDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\UserForm;
use Webkul\Core\Facades\Acl;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

class UserController extends Controller
{
    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(UserDataGrid::class)->process();
        }

        $roles = Role::all();

        return view('admin::settings.users.index', compact('roles'));
    }

    
    public function store(UserForm $request): JsonResponse
    {
        $dat = $request->only([
            'name',
            'email',
            'password',
            'password_confirmation',
            'role_id',
            'status',
        ]);

        if ($dat['password'] ?? null) {
            $dat['password'] = bcrypt($dat['password']);

            $dat['api_token'] = Str::random(80);
        }

        Event::dispatch('user.admin.create.before');

        $admin = Admin::create($dat);

        if (request()->hasFile('image')) {
            $admin->image = current(request()->file('image'))->store('admins/'.$admin->id);

            $admin->save();
        }

        Event::dispatch('user.admin.create.after', $admin);

        return new JsonResponse([
            'message' => trans('admin::app.settings.users.create-success'),
        ]);
    }

    
    public function edit($i): JsonResponse
    {
        $user = Admin::findOrFail($i);

        $roles = Role::all();

        return new JsonResponse([
            'roles' => $roles,
            'user'  => $user,
        ]);
    }

    
    public function update(UserForm $request): JsonResponse
    {
        $i = request()->id;

        $dat = $this->prepareUserData($request, $i);

        if ($dat instanceof \Illuminate\Http\RedirectResponse) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.users.update-success'),
            ]);
        }

        Event::dispatch('user.admin.update.before', $i);

        Admin::update($dat, $i);
        $admin = Admin::find($i);

        if (request()->hasFile('image')) {
            $admin->image = current(request()->file('image'))->store('admins/'.$admin->id);
        } else {
            if (! request()->has('image.image')) {
                if (! empty(request()->input('image.image'))) {
                    Storage::delete($admin->image);
                }

                $admin->image = null;
            }
        }

        $admin->save();

        if (! empty($dat['password'])) {
            Event::dispatch('admin.password.update.after', $admin);
        }

        Event::dispatch('user.admin.update.after', $admin);

        return new JsonResponse([
            'message' => trans('admin::app.settings.users.update-success'),
        ]);
    }

    
    public function destroy($i): JsonResponse
    {
        if (Admin::count() == 1) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.users.last-delete-error'),
            ], 400);
        }

        try {
            Event::dispatch('user.admin.delete.before', $i);

            Admin::delete($i);

            Event::dispatch('user.admin.delete.after', $i);

            return new JsonResponse([
                'message' => trans('admin::app.settings.users.delete-success'),
            ], 200);
        } catch (\Exception $e) {
        }

        return new JsonResponse([
            'message' => trans('admin::app.settings.users.delete-failed'),
        ], 500);
    }

    
    public function confirm($i)
    {
        $user = Admin::findOrFail($i);

        return view('admin::customers.customers.confirm-password', compact('user'));
    }

    
    public function destroySelf(): JsonResponse
    {
        $password = request()->input('password');

        if (Hash::check($password, auth()->guard('admin')->user()->password)) {
            if (Admin::count() == 1) {
                session()->flash('error', trans('admin::app.settings.users.delete-last'));
            } else {
                $i = auth()->guard('admin')->user()->id;

                Event::dispatch('user.admin.delete.before', $i);

                Admin::delete($i);

                Event::dispatch('user.admin.delete.after', $i);

                return new JsonResponse([
                    'redirectUrl' => route('admin.session.create'),
                    'message'     => trans('admin::app.settings.users.delete-success'),
                ]);
            }
        } else {
            return new JsonResponse([
                'message' => trans('admin::app.settings.users.incorrect-password'),
            ], 404);
        }
    }

    
    private function prepareUserData(UserForm $request, $i)
    {
        $dat = $request->validated();

        $user = Admin::find($i);

        
        if (! $dat['password']) {
            unset($dat['password']);
        } else {
            $dat['password'] = bcrypt($dat['password']);
        }

        
        $dat['status'] = isset($dat['status']);

        $isStatusChangedToInactive = ! $dat['status'] && (bool) $user->status;

        if (
            $isStatusChangedToInactive
            && (auth()->guard('admin')->user()->id === (int) $i
                && Admin::countAdminsWithAllAccessAndActiveStatus() === 1
            )
        ) {
            return $this->cannotChangeRedirectResponse('status');
        }

        
        $isRoleChanged = $user->role->permission_type === 'all'
            && isset($dat['role_id'])
            && (int) $dat['role_id'] !== $user->role_id;

        if (
            $isRoleChanged
            && Admin::countAdminsWithAllAccess() === 1
        ) {
            return $this->cannotChangeRedirectResponse('role');
        }

        return $dat;
    }

    
    private function cannotChangeRedirectResponse(string $columnName): \Illuminate\Http\RedirectResponse
    {
        session()->flash('error', trans('admin::app.settings.users.cannot-change', [
            'name' => $columnName,
        ]));

        return redirect()->route('admin.settings.users.index');
    }
}
