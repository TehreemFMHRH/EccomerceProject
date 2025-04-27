<?php

namespace Webkul\Admin\Http\Controllers\Marketing\Communications;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\DataGrids\Marketing\Communications\NewsLetterDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Repositories\SubscribersListRepository;

class SubscriptionController extends Controller
{
    
    public function __construct(protected SubscribersListRepository $subscribersListRepository) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(NewsLetterDataGrid::class)->process();
        }

        return view('admin::marketing.communications.subscribers.index');
    }

    
    public function edit(int $i): JsonResponse
    {
        $subscriber = $this->subscribersListRepository->findOrFail($i);

        return new JsonResponse([
            'data'  => $subscriber,
        ]);
    }

    
    public function update()
    {
        $validatedData = $this->validate(request(), [
            'id'            => 'required',
            'is_subscribed' => 'required|in:0,1',
        ]);

        $subscriber = $this->subscribersListRepository->findOrFail($validatedData['id']);

        $k = $subscriber->customer;

        if ($k) {
            $k->subscribed_to_news_letter = $validatedData['is_subscribed'];

            $k->save();
        }

        $result = $subscriber->update(['is_subscribed' => $validatedData['is_subscribed']]);

        if ($result) {
            return response()->json([
                'message' => trans('admin::app.marketing.communications.subscribers.index.edit.success'),
            ], 200);
        }

        return response()->json([
            'message' => trans('admin::app.marketing.communications.subscribers.index.edit.update-failed'),
        ], 500);
    }

    
    public function destroy(int $i)
    {
        try {
            $this->subscribersListRepository->delete($i);

            return response()->json([
                'message' => trans('admin::app.marketing.communications.subscribers.delete-success'),
            ], 200);
        } catch (\Exception $e) {
            report($e);
        }

        return response()->json([
            'message' => trans('admin::app.marketing.communications.subscribers.delete-failed'),
        ], 500);
    }
}
