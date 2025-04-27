<?php

namespace Webkul\Admin\Http\Controllers\Marketing\Communications;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Marketing\Communications\EventDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Marketing\Repositories\EventRepository;

class EventController extends Controller
{
    
    public function __construct(protected EventRepository $eventRepository) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(EventDataGrid::class)->process();
        }

        return view('admin::marketing.communications.events.index');
    }

    
    public function store()
    {
        $this->validate(request(), [
            'name'        => 'required',
            'description' => 'required',
            'date'        => 'date|required',
        ]);

        Event::dispatch('marketing.events.create.before');

        $event = $this->eventRepository->create(request()->only([
            'name',
            'description',
            'date',
        ]));

        Event::dispatch('marketing.events.create.after', $event);

        return response()->json([
            'message' => trans('admin::app.marketing.communications.events.index.create.success'),
        ], 200);
    }

    
    public function edit(int $i): JsonResponse
    {
        if ($i == 1) {
            return new JsonResponse([
                'message' => trans('admin::app.marketing.communications.events.edit-error'),
            ]);
        }

        $event = $this->eventRepository->findOrFail($i);

        return new JsonResponse($event);
    }

    
    public function update()
    {
        $i = request()->id;

        $this->validate(request(), [
            'name'        => 'required',
            'description' => 'required',
            'date'        => 'date|required',
        ]);

        Event::dispatch('marketing.events.update.before', $i);

        $event = $this->eventRepository->update(request()->only([
            'name',
            'description',
            'date',
        ]), $i);

        Event::dispatch('marketing.events.update.after', $event);

        return response()->json([
            'message' => trans('admin::app.marketing.communications.events.index.edit.success'),
        ], 200);
    }

    
    public function destroy(int $i)
    {
        $this->eventRepository->findOrFail($i);

        try {
            Event::dispatch('marketing.events.delete.before', $i);

            $this->eventRepository->delete($i);

            Event::dispatch('marketing.events.delete.after', $i);

            return response()->json([
                'message' => trans('admin::app.marketing.communications.events.delete-success'),
            ], 200);
        } catch (\Exception $e) {
        }

        return response()->json([
            'message' => trans('admin::app.marketing.communications.events.delete-failed', ['name'  =>  'admin::app.marketing.communications.events.index.event']),
        ], 500);
    }
}
