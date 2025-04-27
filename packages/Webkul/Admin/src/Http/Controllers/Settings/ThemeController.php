<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\DataGrids\Theme\ThemeDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Theme\Models\ThemeCustomization;

class ThemeController extends Controller
{
    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(ThemeDataGrid::class)->process();
        }

        return view('admin::settings.themes.index');
    }

    
    public function store()
    {
        if (request()->has('id')) {
            $this->validate(request(), [
                core()->getRequestedLocaleCode().'.options.*.image' => 'image|extensions:jpeg,jpg,png,svg,webp',
            ]);

            $theme = ThemeCustomization::find(request()->input('id'));

            return ThemeCustomization::uploadImage(request()->all(), $theme);
        }

        $validated = $this->validate(request(), [
            'name'       => 'required',
            'sort_order' => 'required|numeric',
            'type'       => 'required|in:product_carousel,category_carousel,static_content,image_carousel,footer_links,services_content',
            'channel_id' => 'required|in:'.implode(',', (core()->getAllChannels()->pluck('id')->toArray())),
            'theme_code' => 'required',
        ]);

        Event::dispatch('theme_customization.create.before');

        $theme = ThemeCustomization::create($validated);

        Event::dispatch('theme_customization.create.after', $theme);

        return new JsonResponse([
            'redirect_url' => route('admin.settings.themes.edit', $theme->id),
        ]);
    }

    
    public function edit(int $i)
    {
        $theme = ThemeCustomization::find($i);

        return view('admin::settings.themes.edit', compact('theme'));
    }

    
    public function update(int $i)
    {
        $this->validate(request(), [
            'name'       => 'required',
            'sort_order' => 'required|numeric',
            'type'       => 'required|in:product_carousel,category_carousel,static_content,image_carousel,footer_links,services_content',
            'channel_id' => 'required|in:'.implode(',', (core()->getAllChannels()->pluck('id')->toArray())),
            'theme_code' => 'required',
        ]);

        $locale = request('locale');

        $dat = request()->only(
            'locale',
            'type',
            'name',
            'sort_order',
            'channel_id',
            'theme_code',
            'status',
            $locale
        );

        Event::dispatch('theme_customization.update.before', $i);

        $dat['status'] = request()->input('status') == 'on';

        $theme = ThemeCustomization::update($dat, [$i]);

        Event::dispatch('theme_customization.update.after', $theme);

        session()->flash('success', trans('admin::app.settings.themes.update-success'));

        return redirect()->route('admin.settings.themes.index');
    }

    
    public function destroy(int $i)
    {
        Event::dispatch('theme_customization.delete.before', $i);

        ThemeCustomization::delete($i);

        Storage::deleteDirectory('theme/'.$i);

        Event::dispatch('theme_customization.delete.after', $i);

        return new JsonResponse([
            'message' => trans('admin::app.settings.themes.delete-success'),
        ], 200);
    }

    public function massUpdate(MassUpdateRequest $massUpdateRequest): JsonResponse
    {
        $selectedThemeIds = $massUpdateRequest->input('indices');

        ThemeCustomization::massUpdateStatus([
            'status' => $massUpdateRequest->input('value'),
        ], $selectedThemeIds);

        return new JsonResponse([
            'message' => trans('admin::app.settings.themes.update-success'),
        ]);
    }

    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $selectedThemeIds = $massDestroyRequest->input('indices');

        foreach ($selectedThemeIds as $themeId) {
            ThemeCustomization::delete($themeId);
        }

        return new JsonResponse([
            'message' => trans('admin::app.settings.themes.update-success'),
        ]);
    }
}
