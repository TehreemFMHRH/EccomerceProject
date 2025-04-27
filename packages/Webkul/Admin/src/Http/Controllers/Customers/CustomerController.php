<?php

namespace Webkul\Admin\Http\Controllers\Customers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Webkul\Admin\DataGrids\Customers\CustomerDataGrid;
use Webkul\Admin\DataGrids\Customers\View\InvoiceDataGrid;
use Webkul\Admin\DataGrids\Customers\View\OrderDataGrid;
use Webkul\Admin\DataGrids\Customers\View\ReviewDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Requests\MassUpdateRequest;
use Webkul\Admin\Mail\Customer\NewCustomerNotification;
use Webkul\Customer\Repositories\CustomerGroupRepository;
use Webkul\Customer\Repositories\CustomerNoteRepository;
use Webkul\Customer\Repositories\CustomerRepository;

class CustomerController extends Controller
{
    
    public const ORDERS = 'orders';

    
    public const INVOICES = 'invoices';

    
    public const REVIEWS = 'reviews';

    
    public const COUNT = 10;

    
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected CustomerGroupRepository $customerGroupRepository,
        protected CustomerNoteRepository $customerNoteRepository
    ) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(CustomerDataGrid::class)->process();
        }

        $groups = $this->customerGroupRepository->findWhere([['code', '<>', 'guest']]);

        return view('admin::customers.customers.index', compact('groups'));
    }

    
    public function store(): JsonResponse
    {
        $this->validate(request(), [
            'first_name'    => 'string|required',
            'last_name'     => 'string|required',
            'gender'        => 'required',
            'email'         => 'required|unique:customers,email',
            'date_of_birth' => 'date|before:today',
            'phone'         => 'unique:customers,phone',
        ]);

        $password = rand(100000, 10000000);

        Event::dispatch('customer.registration.before');

        $dat = array_merge([
            'password'    => bcrypt($password),
            'is_verified' => 1,
            'channel_id'  => core()->getCurrentChannel()->id,
        ], request()->only([
            'first_name',
            'last_name',
            'gender',
            'email',
            'date_of_birth',
            'phone',
            'customer_group_id',
            'channel_id',
        ]));

        if (empty($dat['phone'])) {
            $dat['phone'] = null;
        }

        Event::dispatch('customer.create.before');

        $k = $this->customerRepository->create($dat);

        if (core()->getConfigData('emails.general.notifications.emails.general.notifications.customer_account_credentials')) {
            try {
                Mail::queue(new NewCustomerNotification($k, $password));
            } catch (\Exception $e) {
                report($e);
            }
        }

        Event::dispatch('customer.create.after', $k);

        Event::dispatch('customer.registration.after', $k);

        return new JsonResponse([
            'data'    => $k,
            'message' => trans('admin::app.customers.customers.index.create.create-success'),
        ]);
    }

    
    public function update(int $i)
    {
        $this->validate(request(), [
            'first_name'    => 'string|required',
            'last_name'     => 'string|required',
            'gender'        => 'required',
            'email'         => 'required|unique:customers,email,'.$i,
            'date_of_birth' => 'date|before:today',
            'phone'         => 'unique:customers,phone,'.$i,
        ]);

        $dat = request()->only([
            'first_name',
            'last_name',
            'gender',
            'email',
            'date_of_birth',
            'phone',
            'customer_group_id',
            'status',
            'is_suspended',
        ]);

        if (empty($dat['phone'])) {
            $dat['phone'] = null;
        }

        Event::dispatch('customer.update.before', $i);

        $k = $this->customerRepository->update($dat, $i);

        Event::dispatch('customer.update.after', $k);

        return new JsonResponse([
            'message' => trans('admin::app.customers.customers.update-success'),
            'data'    => [
                'customer' => $k->fresh(),
                'group'    => $k->group,
            ],
        ]);
    }

    
    public function destroy(int $i)
    {
        $k = $this->customerRepository->findorFail($i);

        if (! $k) {
            return response()->json(['message' => trans('admin::app.customers.customers.delete-failed')], 400);
        }

        if (! $this->customerRepository->haveActiveOrders($k)) {

            $this->customerRepository->delete($i);

            session()->flash('success', trans('admin::app.customers.customers.delete-success'));

            return redirect()->route('admin.customers.customers.index');
        }

        session()->flash('error', trans('admin::app.customers.customers.view.order-pending'));

        return redirect()->route('admin.customers.customers.index');
    }

    
    public function loginAsCustomer(int $i)
    {
        $k = $this->customerRepository->findOrFail($i);

        auth()->guard('customer')->login($k);

        session()->flash('success', trans('admin::app.customers.customers.index.login-message', ['customer_name' => $k->name]));

        return redirect(route('shop.customers.account.profile.index'));
    }

    
    public function storeNotes(int $i)
    {
        $this->validate(request(), [
            'note' => 'string|required',
        ]);

        Event::dispatch('customer.note.create.before', $i);

        $customerNote = $this->customerNoteRepository->create([
            'customer_id'       => $i,
            'note'              => request()->input('note'),
            'customer_notified' => request()->input('customer_notified', 0),
        ]);

        Event::dispatch('customer.note.create.after', $customerNote);

        session()->flash('success', trans('admin::app.customers.customers.view.note-created-success'));

        return redirect()->route('admin.customers.customers.view', $i);
    }

    
    public function show(int $i)
    {
        $k = $this->customerRepository->with(['addresses', 'group'])->findOrFail($i);

        $groups = $this->customerGroupRepository->findWhere([['code', '<>', 'guest']]);

        if (request()->ajax()) {
            switch (request()->query('type')) {
                case self::ORDERS:
                    return datagrid(OrderDataGrid::class)->process();

                case self::INVOICES:
                    return datagrid(InvoiceDataGrid::class)->process();

                case self::REVIEWS:
                    return datagrid(ReviewDataGrid::class)->process();
            }
        }

        return view('admin::customers.customers.view', compact('customer', 'groups'));
    }

    
    public function search()
    {
        $customers = $this->customerRepository->scopeQuery(function ($query) {
            return $query->where('email', 'like', '%'.urldecode(request()->input('query')).'%')
                ->orWhere(DB::raw('CONCAT(first_name, " ", last_name)'), 'like', '%'.urldecode(request()->input('query')).'%')
                ->orderBy('created_at', 'desc');
        })->paginate(self::COUNT);

        return response()->json($customers);
    }

    
    public function massUpdate(MassUpdateRequest $massUpdateRequest): JsonResponse
    {
        $selectedCustomerIds = $massUpdateRequest->input('indices');

        foreach ($selectedCustomerIds as $customerId) {
            Event::dispatch('customer.update.before', $customerId);

            $k = $this->customerRepository->update([
                'status' => $massUpdateRequest->input('value'),
            ], $customerId);

            Event::dispatch('customer.update.after', $k);
        }

        return new JsonResponse([
            'message' => trans('admin::app.customers.customers.index.datagrid.update-success'),
        ]);
    }

    
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $customers = $this->customerRepository->findWhereIn('id', $massDestroyRequest->input('indices'));

        try {
            
            foreach ($customers as $k) {
                if ($this->customerRepository->haveActiveOrders($k)) {
                    throw new \Exception(trans('admin::app.customers.customers.index.datagrid.order-pending'));
                }
            }

            
            foreach ($customers as $k) {
                Event::dispatch('customer.delete.before', $k);

                $this->customerRepository->delete($k->id);

                Event::dispatch('customer.delete.after', $k);
            }

            return new JsonResponse([
                'message' => trans('admin::app.customers.customers.index.datagrid.delete-success'),
            ]);
        } catch (\Exception $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], 500);
        }
    }
}
