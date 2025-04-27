<?php

namespace Webkul\Admin\Http\Controllers\Marketing\Promotions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Marketing\Promotions\CatalogRuleDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\CatalogRuleRequest;
use Webkul\CatalogRule\Repositories\CatalogRuleRepository;

class CatalogRuleController extends Controller
{
    
    public function __construct(protected CatalogRuleRepository $catalogRuleRepository) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(CatalogRuleDataGrid::class)->process();
        }

        return view('admin::marketing.promotions.catalog-rules.index');
    }

    
    public function create()
    {
        return view('admin::marketing.promotions.catalog-rules.create');
    }

    
    public function store(CatalogRuleRequest $catalogRuleRequest)
    {
        Event::dispatch('promotions.catalog_rule.create.before');

        $catalogRule = $this->catalogRuleRepository->create($catalogRuleRequest->all());

        Event::dispatch('promotions.catalog_rule.create.after', $catalogRule);

        session()->flash('success', trans('admin::app.marketing.promotions.catalog-rules.create-success'));

        return redirect()->route('admin.marketing.promotions.catalog_rules.index');
    }

    
    public function edit(int $i)
    {
        $catalogRule = $this->catalogRuleRepository->findOrFail($i);

        return view('admin::marketing.promotions.catalog-rules.edit', compact('catalogRule'));
    }

    
    public function update(CatalogRuleRequest $catalogRuleRequest, int $i)
    {
        $this->catalogRuleRepository->findOrFail($i);

        Event::dispatch('promotions.catalog_rule.update.before', $i);

        $catalogRule = $this->catalogRuleRepository->update($catalogRuleRequest->all(), $i);

        Event::dispatch('promotions.catalog_rule.update.after', $catalogRule);

        session()->flash('success', trans('admin::app.marketing.promotions.catalog-rules.update-success'));

        return redirect()->route('admin.marketing.promotions.catalog_rules.index');
    }

    
    public function destroy(int $i): JsonResponse
    {
        $this->catalogRuleRepository->findOrFail($i);

        try {
            Event::dispatch('promotions.catalog_rule.delete.before', $i);

            $this->catalogRuleRepository->delete($i);

            Event::dispatch('promotions.catalog_rule.delete.after', $i);

            return new JsonResponse([
                'message' => trans('admin::app.marketing.promotions.catalog-rules.delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('admin::app.marketing.promotions.catalog-rules.delete-failed'),
            ], 400);
        }
    }
}
