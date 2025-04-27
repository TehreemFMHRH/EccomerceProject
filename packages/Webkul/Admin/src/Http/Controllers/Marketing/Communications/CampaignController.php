<?php

namespace Webkul\Admin\Http\Controllers\Marketing\Communications;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Marketing\Communications\CampaignDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Marketing\Repositories\CampaignRepository;
use Webkul\Marketing\Repositories\TemplateRepository;

class CampaignController extends Controller
{
    
    public function __construct(
        protected CampaignRepository $campaignRepository,
        protected TemplateRepository $templateRepository,
    ) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(CampaignDataGrid::class)->process();
        }

        return view('admin::marketing.communications.campaigns.index');
    }

    
    public function create()
    {
        $templates = $this->templateRepository->findByField('status', 'active');

        return view('admin::marketing.communications.campaigns.create', compact('templates'));
    }

    
    public function store()
    {
        $validatedData = $this->validate(request(), [
            'name'                  => 'required',
            'subject'               => 'required',
            'marketing_template_id' => 'required',
            'marketing_event_id'    => 'required',
            'channel_id'            => 'required',
            'customer_group_id'     => 'required',
            'status'                => 'sometimes|required|in:0,1',
        ]);

        Event::dispatch('marketing.campaigns.create.before');

        $campaign = $this->campaignRepository->create($validatedData);

        Event::dispatch('marketing.campaigns.create.after', $campaign);

        session()->flash('success', trans('admin::app.marketing.communications.campaigns.create-success'));

        return redirect()->route('admin.marketing.communications.campaigns.index');
    }

    
    public function edit(int $i)
    {
        $campaign = $this->campaignRepository->findOrFail($i);

        $templates = $this->templateRepository->findByField('status', 'active');

        return view('admin::marketing.communications.campaigns.edit', compact('campaign', 'templates'));
    }

    
    public function update(int $i)
    {
        $validatedData = $this->validate(request(), [
            'name'                  => 'required',
            'subject'               => 'required',
            'marketing_template_id' => 'required',
            'marketing_event_id'    => 'required',
            'channel_id'            => 'required',
            'customer_group_id'     => 'required',
        ]);

        Event::dispatch('marketing.campaigns.update.before', $i);

        $campaign = $this->campaignRepository->update([
            ...$validatedData,
            'status' => request()->input('status') ? 1 : 0,
        ], $i);

        Event::dispatch('marketing.campaigns.update.after', $campaign);

        session()->flash('success', trans('admin::app.marketing.communications.campaigns.update-success'));

        return redirect()->route('admin.marketing.communications.campaigns.index');
    }

    
    public function destroy(int $i): JsonResponse
    {
        try {
            Event::dispatch('marketing.campaigns.delete.before', $i);

            $this->campaignRepository->delete($i);

            Event::dispatch('marketing.campaigns.delete.after', $i);

            return new JsonResponse([
                'message' => trans('admin::app.marketing.communications.campaigns.delete-success'),
            ]);
        } catch (\Exception $e) {
        }

        return new JsonResponse([
            'message' => trans('admin::app.marketing.communications.campaigns.delete-failed', [
                'name' => 'admin::app.marketing.communications.campaigns.email-campaign',
            ]),
        ], 500);
    }
}
