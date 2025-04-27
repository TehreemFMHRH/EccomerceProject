<?php

namespace Webkul\Admin\Http\Controllers\Catalog;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Catalog\AttributeFamilyDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Rules\Code;

class AttributeFamilyController extends Controller
{
    
    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeRepository $attributeRepository
    ) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(AttributeFamilyDataGrid::class)->process();
        }

        return view('admin::catalog.families.index');
    }

    
    public function create()
    {
        $attributeFamily = $this->attributeFamilyRepository->with(['attribute_groups.custom_attributes'])->findOneByField('code', 'default');

        $customAttributes = $this->attributeRepository->all(['id', 'code', 'admin_name', 'type', 'is_user_defined']);

        return view('admin::catalog.families.create', compact('attributeFamily', 'customAttributes'));
    }

    
    public function store()
    {
        $this->validate(request(), [
            'code'                      => ['required', 'unique:attribute_families,code', new Code],
            'name'                      => 'required',
            'attribute_groups.*.code'   => 'required',
            'attribute_groups.*.name'   => 'required',
            'attribute_groups.*.column' => 'required|in:1,2',
        ]);

        Event::dispatch('catalog.attribute_family.create.before');

        $attributeFamily = $this->attributeFamilyRepository->create([
            'attribute_groups' => request('attribute_groups'),
            'code'             => request('code'),
            'name'             => request('name'),
        ]);

        Event::dispatch('catalog.attribute_family.create.after', $attributeFamily);

        session()->flash('success', trans('admin::app.catalog.families.create-success'));

        return redirect()->route('admin.catalog.families.index');
    }

    
    public function edit(int $i)
    {
        $attributeFamily = $this->attributeFamilyRepository->with(['attribute_groups.custom_attributes'])->findOrFail($i, ['*']);

        $customAttributes = $this->attributeRepository->all(['id', 'code', 'admin_name', 'type', 'is_user_defined']);

        return view('admin::catalog.families.edit', compact('attributeFamily', 'customAttributes'));
    }

    
    public function update(int $i)
    {
        $this->validate(request(), [
            'code'                      => ['required', 'unique:attribute_families,code,'.$i, new Code],
            'name'                      => 'required',
            'attribute_groups.*.code'   => 'required',
            'attribute_groups.*.name'   => 'required',
            'attribute_groups.*.column' => 'required|in:1,2',
        ]);

        Event::dispatch('catalog.attribute_family.update.before', $i);

        $attributeFamily = $this->attributeFamilyRepository->update([
            'attribute_groups' => request('attribute_groups'),
            'code'             => request('code'),
            'name'             => request('name'),
        ], $i);

        Event::dispatch('catalog.attribute_family.update.after', $attributeFamily);

        session()->flash('success', trans('admin::app.catalog.families.update-success'));

        return redirect()->route('admin.catalog.families.index');
    }

    
    public function destroy(int $i): JsonResponse
    {
        $attributeFamily = $this->attributeFamilyRepository->findOrFail($i);

        if ($this->attributeFamilyRepository->count() == 1) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.families.last-delete-error'),
            ], 400);
        }

        if ($attributeFamily->products()->count()) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.families.attribute-product-error'),
            ], 400);
        }

        try {
            Event::dispatch('catalog.attribute_family.delete.before', $i);

            $this->attributeFamilyRepository->delete($i);

            Event::dispatch('catalog.attribute_family.delete.after', $i);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.families.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.families.delete-failed', ['name' => 'admin::app.catalog.families.family']),
        ], 500);
    }
}
