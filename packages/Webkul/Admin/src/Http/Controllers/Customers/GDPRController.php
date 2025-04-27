<?php

namespace Webkul\Admin\Http\Controllers\Customers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Webkul\Admin\DataGrids\Customers\GDPRDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\GDPR\Repositories\GDPRDataRequestRepository;

class GDPRController extends Controller
{
    
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected GDPRDataRequestRepository $gdprDataRequestRepository
    ) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(GDPRDataGrid::class)->process();
        }

        return view('admin::customers.gdpr.index');
    }

    
    public function edit(int $i)
    {
        try {
            $request = $this->gdprDataRequestRepository->findOrFail($i);

            return new JsonResponse([
                'data' => $request,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('admin::app.customers.gdpr.index.attribute-reason-error'),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    
    public function update(int $i)
    {
        try {
            Event::dispatch('customer.gdpr-request.update.before');

            $gdprRequest = $this->gdprDataRequestRepository->update(request()->all(), $i);

            Event::dispatch('customer.account.gdpr-request.update.after', $gdprRequest);

            return response()->json([
                'message' => trans(key: 'admin::app.customers.gdpr.index.update-success'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('admin::app.customers.gdpr.index.update-success-unsent-email'),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    
    public function delete(int $i)
    {
        try {
            $gdprRequest = $this->gdprDataRequestRepository->findOrFail($i);

            $gdprRequest->delete();

            return new JsonResponse([
                'message' => trans('admin::app.customers.gdpr.index.delete-success'),
            ]);
        } catch (\Exception $e) {
        }

        return new JsonResponse([
            'message' => trans('admin::app.customers.gdpr.index.attribute-reason-error'),
        ], 500);
    }
}
