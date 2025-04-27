<?php

namespace Webkul\Admin\Http\Controllers\Marketing\SearchSEO;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\DataGrids\Marketing\SearchSEO\SearchTermDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Marketing\Repositories\SearchTermRepository;

class SearchTermController extends Controller
{
    
    public function __construct(public SearchTermRepository $searchTermRepository) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(SearchTermDataGrid::class)->process();
        }

        return view('admin::marketing.search-seo.search-terms.index');
    }

    
    public function store(): JsonResponse
    {
        $this->validate(request(), [
            'term'         => 'required',
            'redirect_url' => 'url:http,https',
            'channel_id'   => 'required|exists:channels,id',
            'locale'       => 'required|exists:locales,code',
        ]);

        Event::dispatch('marketing.search_seo.search_terms.create.before');

        $searchTerm = $this->searchTermRepository->create(request()->only([
            'term',
            'redirect_url',
            'channel_id',
            'locale',
        ]));

        Event::dispatch('marketing.search_seo.search_terms.create.after', $searchTerm);

        return new JsonResponse([
            'message' => trans('admin::app.marketing.search-seo.search-terms.index.create.success'),
        ]);
    }

    
    public function update(): JsonResponse
    {
        $i = request()->id;

        $this->validate(request(), [
            'term'         => 'required',
            'redirect_url' => 'url:http,https',
            'channel_id'   => 'required|exists:channels,id',
            'locale'       => 'required|exists:locales,code',
        ]);

        Event::dispatch('marketing.search_seo.search_terms.update.before', $i);

        $searchTerm = $this->searchTermRepository->update(request()->only([
            'term',
            'results',
            'uses',
            'redirect_url',
            'channel_id',
            'locale',
        ]), $i);

        Event::dispatch('marketing.search_seo.search_terms.update.after', $searchTerm);

        return new JsonResponse([
            'message' => trans('admin::app.marketing.search-seo.search-terms.index.edit.success'),
        ]);
    }

    
    public function destroy($i)
    {
        try {
            Event::dispatch('marketing.search_seo.search_terms.delete.before', $i);

            $this->searchTermRepository->delete($i);

            Event::dispatch('marketing.search_seo.search_terms.delete.after', $i);

            return response()->json([
                'message' => trans('admin::app.marketing.search-seo.search-terms.index.edit.delete-success'),
            ], 200);
        } catch (\Exception $e) {
        }

        return response()->json([
            'message' => trans('admin::app.marketing.search-seo.search-terms.delete-failed'),
        ], 500);
    }

    
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $searchTermIds = $massDestroyRequest->input('indices');

        try {
            foreach ($searchTermIds as $searchTermId) {
                $searchTerm = $this->searchTermRepository->find($searchTermId);

                if (isset($searchTerm)) {
                    Event::dispatch('marketing.search_seo.search_terms.delete.before', $searchTermId);

                    $this->searchTermRepository->delete($searchTermId);

                    Event::dispatch('marketing.search_seo.search_terms.delete.after', $searchTermId);
                }
            }

            return new JsonResponse([
                'message' => trans('admin::app.marketing.search-seo.search-terms.index.datagrid.mass-delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
