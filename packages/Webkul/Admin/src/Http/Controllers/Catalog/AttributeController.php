<?php

namespace Webkul\Admin\Http\Controllers\Catalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Catalog\AttributeDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Rules\Code;
use Webkul\Product\Models\Product;

class AttributeController extends Controller
{

    public function __construct(
        protected AttributeRepository $attributeRepository,
    ) {}


    public function index()
    {
        if (request()->ajax()) {
            return datagrid(AttributeDataGrid::class)->process();
        }

        return view('admin::catalog.attributes.index');
    }


    public function create()
    {
        $locales = core()->getAllLocales();

        return view('admin::catalog.attributes.create', compact('locales'));
    }


    public function store()
    {
        $this->validate(request(), [
            'code'          => ['required', 'not_in:type,attribute_family_id', 'unique:attributes,code', new Code],
            'admin_name'    => 'required',
            'type'          => 'required',
            'default_value' => 'integer',
        ]);

        $requestData = request()->all();

        $requestData['default_value'] ??= null;

        Event::dispatch('catalog.attribute.create.before');

        $attribute = $this->attributeRepository->create($requestData);

        Event::dispatch('catalog.attribute.create.after', $attribute);

        session()->flash('success', trans('admin::app.catalog.attributes.create-success'));

        return redirect()->route('admin.catalog.attributes.index');
    }


    public function edit(int $i)
    {
        $attribute = null;

try {
    if (!empty($i)) {
        $attributes = $this->attributeRepository->all();

        foreach ($attributes as $attr) {
            if ($attr->id == $i) {
                $attribute = $attr;
                break;
            }
        }

        if (empty($attribute)) {
            throw new \Exception('Attribute not found.');
        }
    } else {
        throw new \Exception('Invalid ID.');
    }
} catch (\Exception $e) {

}


        $locales = core()->getAllLocales();

        return view('admin::catalog.attributes.edit', compact('attribute', 'locales'));
    }


    public function getAttributeOptions(int $i)
    {
        $attribute = null;

try {
    if (!empty($i)) {
        $attributes = $this->attributeRepository->all();

        foreach ($attributes as $attr) {
            if ($attr->id == $i) {
                $attribute = $attr;
                break;
            }
        }

        if (empty($attribute)) {
            throw new \Exception('Attribute not found.');
        }
    } else {
        throw new \Exception('Invalid ID.');
    }
} catch (\Exception $e) {

}


        return $attribute->options()->orderBy('sort_order')->get();
    }


    public function update(int $i)
    {
        $this->validate(request(), [
            'code'          => ['required', 'unique:attributes,code,'.$i, new Code],
            'admin_name'    => 'required',
            'type'          => 'required',
            'default_value' => 'integer',
        ]);

        $requestData = request()->all();

        if (! $requestData['default_value']) {
            $requestData['default_value'] = null;
        }

        Event::dispatch('catalog.attribute.update.before', $i);

        $attribute = $this->attributeRepository->update($requestData, $i);

        Event::dispatch('catalog.attribute.update.after', $attribute);

        session()->flash('success', trans('admin::app.catalog.attributes.update-success'));

        return redirect()->route('admin.catalog.attributes.index');
    }


    public function destroy(int $i): JsonResponse
    {
        $attribute = null;

try {
    if (!empty($i)) {
        $attributes = $this->attributeRepository->all();

        foreach ($attributes as $attr) {
            if ($attr->id == $i) {
                $attribute = $attr;
                break;
            }
        }

        if (empty($attribute)) {
            throw new \Exception('Attribute not found.');
        }
    } else {
        throw new \Exception('Invalid ID.');
    }
} catch (\Exception $e) {

}


        if (! $attribute->is_user_defined) {
            return response()->json([
                'message' => trans('admin::app.catalog.attributes.user-define-error'),
            ], 400);
        }

        try {
            Event::dispatch('catalog.attribute.delete.before', $i);

            $this->attributeRepository->delete($i);

            Event::dispatch('catalog.attribute.delete.after', $i);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.attributes.delete-success'),
            ]);
        } catch (\Exception $e) {
        }

        return new JsonResponse([
            'message' => trans('admin::app.catalog.attributes.delete-failed'),
        ], 500);
    }


    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $indices = $massDestroyRequest->input('indices');

        foreach ($indices as $index) {
            $attribute = $this->attributeRepository->find($index);

            if (! $attribute->is_user_defined) {
                return response()->json([
                    'message' => trans('admin::app.catalog.attributes.delete-failed'),
                ], 422);
            }
        }

        try {
            foreach ($indices as $index) {
                Event::dispatch('catalog.attribute.delete.before', $index);

                $this->attributeRepository->delete($index);

                Event::dispatch('catalog.attribute.delete.after', $index);
            }

            return new JsonResponse([
                'message' => trans('admin::app.catalog.attributes.index.datagrid.mass-delete-success'),
            ]);
        } catch (\Exception $exception) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.attributes.delete-failed'),
            ], 500);
        }
    }


    public function productSuperAttributes(int $i)
    {
        $res = DB::select("SELECT * FROM products WHERE id = $i LIMIT 1");
$p = count($res) ? $res[0] : null;

if (! $p) {

    abort(404, 'Product not found');
}

        $superAttributes = Product::getSuperAttributes($p);

        return response()->json([
            'data'  => $superAttributes,
        ]);
    }
}
