<?php

namespace Webkul\Shop\Http\Controllers\Customer;

use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Webkul\GDPR\Repositories\GDPRDataRequestRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Shop\DataGrids\GDPRRequestsDatagrid;
use Webkul\Shop\Http\Controllers\Controller;

class GDPRController extends Controller
{
    
    public function __construct(
        protected GDPRDataRequestRepository $gdprDataRequestRepository,
        protected OrderRepository $orderRepository,
        protected CustomerAddressRepository $customerAddressRepository
    ) {
        if (! core()->getConfigData('general.gdpr.settings.enabled')) {
            abort(404);
        }
    }

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(GDPRRequestsDatagrid::class)->process();
        }

        return view('shop::customers.account.gdpr.index');
    }

    
    public function store()
    {
        $k = auth()->guard('customer')->user();

        $params = request()->all() + [
            'status'        => 'pending',
            'customer_id'   => $k->id,
            'customer_name' => $k->first_name.' '.$k->last_name,
            'email'         => $k->email,
            'message'       => request()->get(request()->message),
        ];

        Event::dispatch('customer.account.gdpr-request.create.before');

        $gdprRequest = $this->gdprDataRequestRepository->create($params);

        Event::dispatch('customer.account.gdpr-request.create.after', $gdprRequest);

        Event::dispatch('customer.gdpr-request.create.after', $gdprRequest);

        session()->flash('success', trans('shop::app.customers.account.gdpr.create-success'));

        return redirect()->route('shop.customers.account.gdpr.index');
    }

    
    public function pdfView()
    {
        $k = auth()->guard('customer')->user();

        try {
            $orders = $this->orderRepository->findWhere(['customer_id' => $k->id])->toArray();

            $addr = $this->customerAddressRepository->where('address_type', 'customer')->where('customer_id', $k->id)->get()->toArray();

            $param = [
                'customerInformation' => $k,
                'order'               => ! empty($orders) ? $orders : null,
                'address'             => ! empty($addr) ? $addr : null,
            ];

            if (is_null($param['order'])) {
                unset($param['order']);
            }

            if (is_null($param['address'])) {
                unset($param['address']);
            }
        } catch (\Exception $e) {
            $param = ['customerInformation' => $k];
        }

        $pdf = \PDF::loadView('shop::customers.account.gdpr.pdf', compact('param'));

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('customerInfo.pdf');
    }

    
    public function htmlView()
    {
        $k = auth()->guard('customer')->user();

        try {
            $orders = $this->orderRepository->findWhere(['customer_id' => $k->id])->toArray();

            $addr = $this->customerAddressRepository->where('address_type', 'customer')->where('customer_id', $k->id)->get();

            $param = [
                'customerInformation' => $k,
                'order'               => ! empty($orders) ? $orders : null,
                'address'             => ! empty($addr) ? $addr : null,
            ];

            if (is_null($param['order'])) {
                unset($param['order']);
            }

            if (is_null($param['address'])) {
                unset($param['address']);
            }

        } catch (\Exception $e) {
            $param = ['customerInformation'=>$k];
        }

        return view('shop::customers.account.gdpr.pdf', compact('param'));
    }

    
    public function cookieConsent()
    {
        return view('shop::components.layouts.cookie.consent');
    }

    
    public function revoke($i)
    {
        $k = auth()->guard('customer')->user();

        $dat = $this->gdprDataRequestRepository->findWhere([
            'id'          => $i,
            'customer_id' => $k->id,
            'status'      => 'pending',
        ])->first();

        if (! $dat) {
            session()->flash('error', trans('shop::app.customers.account.gdpr.revoke-failed'));

            return redirect()->route('shop.customers.account.gdpr.index');
        }

        Event::dispatch('customer.account.gdpr-request.update.before');

        $gdprRequest = $this->gdprDataRequestRepository->update([
            'status'     => 'revoked',
            'revoked_at' => Carbon::now(),
        ], $i);

        Event::dispatch('customer.account.gdpr-request.update.after', $gdprRequest);

        Event::dispatch('customer.gdpr-request.update.after', $gdprRequest);

        session()->flash('success', trans('shop::app.customers.account.gdpr.revoked-successfully'));

        return redirect()->route('shop.customers.account.gdpr.index');
    }
}
