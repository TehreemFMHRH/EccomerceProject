<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Settings\RolesDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;

class RoleController extends Controller
{
    
    public function __construct(
        protected RoleRepository $roleRepository,
        protected AdminRepository $adminRepository
    ) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(RolesDataGrid::class)->process();
        }

        return view('admin::settings.roles.index');
    }

    
    public function create()
    {
        return view('admin::settings.roles.create');
    }

    
    public function store()
    {
        $this->validate(request(), [
            'name'            => 'required',
            'permission_type' => 'required|in:all,custom',
            'description'     => 'required',
        ]);

        if (request('permission_type') == 'custom') {
            $this->validate(request(), [
                'permissions' => 'required',
            ]);
        }

        Event::dispatch('user.role.create.before');

        $dat = request()->only([
            'name',
            'description',
            'permission_type',
            'permissions',
        ]);

        $role = $this->roleRepository->create($dat);

        Event::dispatch('user.role.create.after', $role);

        session()->flash('success', trans('admin::app.settings.roles.create-success'));

        return redirect()->route('admin.settings.roles.index');
    }

    
    public function edit(int $i)
    {
        $role = $this->roleRepository->findOrFail($i);

        return view('admin::settings.roles.edit', compact('role'));
    }

    
    public function update(int $i)
    {
        $this->validate(request(), [
            'name'            => 'required',
            'permission_type' => 'required|in:all,custom',
            'description'     => 'required',
        ]);

        
        $isChangedFromAll = request('permission_type') == 'custom' && $this->roleRepository->find($i)->permission_type == 'all';

        if (
            $isChangedFromAll
            && $this->adminRepository->countAdminsWithAllAccess() === 1
        ) {
            session()->flash('error', trans('admin::app.settings.roles.being-used'));

            return redirect()->route('admin.settings.roles.index');
        }

        $dat = array_merge(request()->only([
            'name',
            'description',
            'permission_type',
        ]), [
            'permissions' => request()->has('permissions') ? request('permissions') : [],
        ]);

        Event::dispatch('user.role.update.before', $i);

        $role = $this->roleRepository->update($dat, $i);

        Event::dispatch('user.role.update.after', $role);

        session()->flash('success', trans('admin::app.settings.roles.update-success'));

        return redirect()->route('admin.settings.roles.index');
    }

    
    public function destroy(int $i): JsonResponse
    {
        $role = $this->roleRepository->findOrFail($i);

        if ($role->admins->count() >= 1) {
            return new JsonResponse(['message' => trans('admin::app.settings.roles.being-used', [
                'name'   => 'admin::app.settings.roles.index.title',
                'source' => 'admin::app.settings.roles.index.admin-user',
            ])], 400);
        }

        if ($this->roleRepository->count() == 1) {
            return new JsonResponse([
                'message' => trans(
                    'admin::app.settings.roles.last-delete-error'
                ),
            ], 400);
        }

        try {
            Event::dispatch('user.role.delete.before', $i);

            $this->roleRepository->delete($i);

            Event::dispatch('user.role.delete.after', $i);

            return new JsonResponse(['message' => trans('admin::app.settings.roles.delete-success')]);
        } catch (\Exception $e) {
        }

        return new JsonResponse([
            'message' => trans(
                'admin::app.settings.roles.delete-failed'
            ),
        ], 500);
    }
}
