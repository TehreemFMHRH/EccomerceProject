<?php

namespace Webkul\Admin\Http\Controllers\Customers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Customers\ReviewDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Product\Repositories\ProductReviewRepository;

class ReviewController extends Controller
{
    
    public function __construct(protected ProductReviewRepository $productReviewRepository) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(ReviewDataGrid::class)->process();
        }

        return view('admin::customers.reviews.index');
    }

    
    public function edit(int $i): JsonResponse
    {
        $review = $this->productReviewRepository->with(['images', 'product'])->findOrFail($i);

        $review->date = $review->created_at->format('Y-m-d');

        return new JsonResponse([
            'data' => $review,
        ]);
    }

    
    public function update(int $i)
    {
        $this->validate(request(), [
            'status' => 'required|in:approved,disapproved,pending',
        ]);

        Event::dispatch('customer.review.update.before', $i);

        $review = $this->productReviewRepository->update([
            'status' => request()->input('status'),
        ], $i);

        Event::dispatch('customer.review.update.after', $review);

        return new JsonResponse([
            'message' => trans('admin::app.customers.reviews.update-success'),
        ]);
    }

    
    public function destroy(int $i): JsonResponse
    {
        try {
            Event::dispatch('customer.review.delete.before', $i);

            $this->productReviewRepository->delete($i);

            Event::dispatch('customer.review.delete.after', $i);

            return new JsonResponse([
                'message' => trans('admin::app.customers.reviews.index.datagrid.delete-success', ['name' => 'Review']),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('admin::app.response.delete-failed', ['name' => 'Review']),
            ], 500);
        }
    }

    
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $indices = $massDestroyRequest->input('indices');

        try {
            foreach ($indices as $index) {
                Event::dispatch('customer.review.delete.before', $index);

                $this->productReviewRepository->delete($index);

                Event::dispatch('customer.review.delete.after', $index);
            }

            return new JsonResponse([
                'message' => trans('admin::app.customers.reviews.index.datagrid.mass-delete-success'),
            ], 200);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function massUpdate(MassUpdateRequest $massUpdateRequest): JsonResponse
    {
        $indices = $massUpdateRequest->input('indices');

        foreach ($indices as $i) {
            Event::dispatch('customer.review.update.before', $i);

            $review = $this->productReviewRepository->update([
                'status' => $massUpdateRequest->input('value'),
            ], $i);

            Event::dispatch('customer.review.update.after', $review);
        }

        return new JsonResponse([
            'message' => trans('admin::app.customers.reviews.index.datagrid.mass-update-success'),
        ], 200);
    }
}
