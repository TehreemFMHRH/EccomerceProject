<?php

namespace Webkul\Admin\Http\Controllers\CMS;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\CMS\CMSPageDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\CMS\Repositories\PageRepository;

class PageController extends Controller
{
    
    public function __construct(protected PageRepository $pageRepository) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(CMSPageDataGrid::class)->process();
        }

        return view('admin::cms.index');
    }

    
    public function create()
    {
        return view('admin::cms.create');
    }

    
    public function store()
    {
        $this->validate(request(), [
            'url_key'      => ['required', 'unique:cms_page_translations,url_key', new \Webkul\Core\Rules\Slug],
            'page_title'   => 'required',
            'channels'     => 'required',
            'html_content' => 'required',
        ]);

        Event::dispatch('cms.page.create.before');

        $page = $this->pageRepository->create(request()->only([
            'page_title',
            'channels',
            'html_content',
            'meta_title',
            'url_key',
            'meta_keywords',
            'meta_description',
        ]));

        Event::dispatch('cms.page.create.after', $page);

        session()->flash('success', trans('admin::app.cms.create-success'));

        return redirect()->route('admin.cms.index');
    }

    
    public function edit(int $i)
    {
        $page = $this->pageRepository->findOrFail($i);

        return view('admin::cms.edit', compact('page'));
    }

    
    public function update(int $i)
    {
        $locale = core()->getRequestedLocaleCode();

        $this->validate(request(), [
            $locale.'.url_key'      => ['required', new \Webkul\Core\Rules\Slug, function ($attribute, $va, $fail) use ($i) {
                if (! $this->pageRepository->isUrlKeyUnique($i, $va)) {
                    $fail(trans('admin::app.cms.index.already-taken', ['name' => 'Page']));
                }
            }],
            $locale.'.page_title'     => 'required',
            $locale.'.html_content'   => 'required',
            'channels'                => 'required',
        ]);

        Event::dispatch('cms.page.update.before', $i);

        $page = $this->pageRepository->update([
            $locale    => request()->input($locale),
            'channels' => request()->input('channels'),
            'locale'   => $locale,
        ], $i);

        Event::dispatch('cms.page.update.after', $page);

        session()->flash('success', trans('admin::app.cms.update-success'));

        return redirect()->route('admin.cms.index');
    }

    
    public function delete(int $i): JsonResponse
    {
        try {
            Event::dispatch('cms.page.delete.before', $i);

            $this->pageRepository->delete($i);

            Event::dispatch('cms.page.delete.after', $i);

            return new JsonResponse(['message' => trans('admin::app.cms.delete-success')]);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => trans('admin::app.cms.no-resource')]);
        }
    }

    
    public function massDelete(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $indices = $massDestroyRequest->input('indices');

        foreach ($indices as $index) {
            Event::dispatch('cms.page.delete.before', $index);

            $this->pageRepository->delete($index);

            Event::dispatch('cms.page.delete.after', $index);
        }

        return new JsonResponse([
            'message' => trans('admin::app.cms.index.datagrid.mass-delete-success'),
        ], 200);
    }
}
