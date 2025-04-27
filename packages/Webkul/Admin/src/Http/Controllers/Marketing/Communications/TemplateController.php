<?php

namespace Webkul\Admin\Http\Controllers\Marketing\Communications;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Marketing\Communications\EmailTemplateDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Marketing\Repositories\TemplateRepository;

class TemplateController extends Controller
{
    
    public function __construct(protected TemplateRepository $templateRepository) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(EmailTemplateDataGrid::class)->process();
        }

        return view('admin::marketing.communications.templates.index');
    }

    
    public function create()
    {
        return view('admin::marketing.communications.templates.create');
    }

    
    public function store()
    {
        $this->validate(request(), [
            'name'    => 'required',
            'status'  => 'required|in:active,inactive,draft',
            'content' => 'required',
        ]);

        Event::dispatch('marketing.templates.create.before');

        $template = $this->templateRepository->create(request()->only([
            'name',
            'status',
            'content',
        ]));

        Event::dispatch('marketing.templates.create.after', $template);

        session()->flash('success', trans('admin::app.marketing.communications.templates.create.create-success'));

        return redirect()->route('admin.marketing.communications.email_templates.index');
    }

    
    public function edit(int $i)
    {
        $template = $this->templateRepository->findOrFail($i);

        return view('admin::marketing.communications.templates.edit', compact('template'));
    }

    
    public function update(int $i)
    {
        $this->validate(request(), [
            'name'    => 'required',
            'status'  => 'required|in:active,inactive,draft',
            'content' => 'required',
        ]);

        Event::dispatch('marketing.templates.update.before', $i);

        $template = $this->templateRepository->update(request()->only([
            'name',
            'status',
            'content',
        ]), $i);

        Event::dispatch('marketing.templates.update.after', $template);

        session()->flash('success', trans('admin::app.marketing.communications.templates.edit.update-success'));

        return redirect()->route('admin.marketing.communications.email_templates.index');
    }

    
    public function destroy(int $i): JsonResponse
    {
        try {
            Event::dispatch('marketing.templates.delete.before', $i);

            $this->templateRepository->delete($i);

            Event::dispatch('marketing.templates.delete.after', $i);

            return new JsonResponse([
                'message' => trans('admin::app.marketing.communications.templates.delete-success'),
            ]);
        } catch (\Exception $e) {
        }

        return new JsonResponse([
            'message' => trans('admin::app.marketing.communications.templates.delete-failed', [
                'name' => 'admin::app.marketing.communications.templates.email-template',
            ]),
        ], 400);
    }
}
