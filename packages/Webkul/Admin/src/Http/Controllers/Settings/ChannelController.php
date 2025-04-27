<?php

namespace Webkul\Admin\Http\Controllers\Settings;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Settings\ChannelDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Repositories\ChannelRepository;

class ChannelController extends Controller
{
    
    public function __construct(protected ChannelRepository $channelRepository) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(ChannelDataGrid::class)->process();
        }

        return view('admin::settings.channels.index');
    }

    
    public function create()
    {
        return view('admin::settings.channels.create');
    }

    
    public function store()
    {
        $dat = $this->validate(request(), [
            /* general */
            'code'                  => ['required', 'unique:channels,code', new \Webkul\Core\Rules\Code],
            'name'                  => 'required',
            'description'           => 'nullable',
            'inventory_sources'     => 'required|array|min:1',
            'root_category_id'      => 'required',
            'hostname'              => 'unique:channels,hostname',

            /* currencies and locales */
            'locales'               => 'required|array|min:1',
            'default_locale_id'     => 'required|in_array:locales.*',
            'currencies'            => 'required|array|min:1',
            'base_currency_id'      => 'required|in_array:currencies.*',

            /* design */
            'theme'                 => 'nullable',
            'logo.*'                => 'nullable|mimes:bmp,jpeg,jpg,png,webp',
            'favicon.*'             => 'nullable|mimes:bmp,jpeg,jpg,png,webp,ico',

            /* seo */
            'seo_title'             => 'required|string',
            'seo_description'       => 'required|string',
            'seo_keywords'          => 'required|string',

            /* maintenance mode */
            'is_maintenance_on'     => 'boolean',
            'maintenance_mode_text' => 'nullable',
            'allowed_ips'           => 'nullable',
        ]);

        $dat = $this->setSEOContent($dat);

        Event::dispatch('core.channel.create.before');

        $channel = $this->channelRepository->create($dat);

        Event::dispatch('core.channel.create.after', $channel);

        session()->flash('success', trans('admin::app.settings.channels.create.create-success'));

        return redirect()->route('admin.settings.channels.index');
    }

    
    public function edit(int $i)
    {
        $channel = $this->channelRepository->with(['locales', 'currencies'])->findOrFail($i);

        return view('admin::settings.channels.edit', compact('channel'));
    }

    
    public function update(int $i)
    {
        $locale = core()->getRequestedLocaleCode();

        $dat = $this->validate(request(), [
            /* general */
            'code'                             => ['required', 'unique:channels,code,'.$i, new \Webkul\Core\Rules\Code],
            $locale.'.name'                    => 'required',
            $locale.'.description'             => 'nullable',
            'inventory_sources'                => 'required|array|min:1',
            'root_category_id'                 => 'required',
            'hostname'                         => 'unique:channels,hostname,'.$i,

            /* currencies and locales */
            'locales'                          => 'required|array|min:1',
            'default_locale_id'                => 'required|in_array:locales.*',
            'currencies'                       => 'required|array|min:1',
            'base_currency_id'                 => 'required|in_array:currencies.*',

            /* design */
            'theme'                            => 'nullable',
            'logo.*'                           => 'nullable|mimes:bmp,jpeg,jpg,png,webp',
            'favicon.*'                        => 'nullable|mimes:bmp,jpeg,jpg,png,webp,ico',

            /* seo */
            $locale.'.seo_title'               => 'required|string',
            $locale.'.seo_description'         => 'required|string',
            $locale.'.seo_keywords'            => 'required|string',

            /* maintenance mode */
            'is_maintenance_on'                => 'boolean',
            $locale.'.maintenance_mode_text'   => 'nullable',
            'allowed_ips'                      => 'nullable',
        ]);

        $dat['is_maintenance_on'] = request()->input('is_maintenance_on') == '1';

        $dat = $this->setSEOContent($dat, $locale);

        Event::dispatch('core.channel.update.before', $i);

        $channel = $this->channelRepository->update($dat, $i);

        Event::dispatch('core.channel.update.after', $channel);

        if ($channel->base_currency->code !== session()->get('currency')) {
            session()->put('currency', $channel->base_currency->code);
        }

        session()->flash('success', trans('admin::app.settings.channels.edit.update-success'));

        return redirect()->route('admin.settings.channels.index');
    }

    
    public function destroy(int $i): JsonResponse
    {
        $channel = $this->channelRepository->findOrFail($i);

        if ($channel->code == config('app.channel')) {
            return new JsonResponse([
                'message'    => trans('admin::app.settings.channels.index.last-delete-error'),
            ], 400);
        }

        try {
            Event::dispatch('core.channel.delete.before', $i);

            $this->channelRepository->delete($i);

            Event::dispatch('core.channel.delete.after', $i);

            return new JsonResponse([
                'message'    => trans('admin::app.settings.channels.index.delete-success'),
            ], 200);
        } catch (\Exception $e) {
        }

        return new JsonResponse([
            'message'    => trans('admin::app.settings.channels.index.delete-failed'),
        ], 500);
    }

    
    private function setSEOContent(array $dat, $locale = null)
    {
        $editedData = $dat;

        if ($locale) {
            $editedData = $dat[$locale];
        }

        $editedData['home_seo']['meta_title'] = $editedData['seo_title'];
        $editedData['home_seo']['meta_description'] = $editedData['seo_description'];
        $editedData['home_seo']['meta_keywords'] = $editedData['seo_keywords'];

        $editedData = $this->unsetKeys($editedData, ['seo_title', 'seo_description', 'seo_keywords']);

        if ($locale) {
            $dat[$locale] = $editedData;
            $editedData = $dat;
        }

        return $editedData;
    }

    
    private function unsetKeys($dat, $keys)
    {
        foreach ($keys as $key) {
            unset($dat[$key]);
        }

        return $dat;
    }
}
