<?php

namespace Webkul\Customer\Repositories;

use Illuminate\Support\Facades\Storage;
use Webkul\Core\Eloquent\Repository;
use Webkul\Sales\Models\Order;

class CustomerRepository extends Repository
{

    public function model(): string
    {
        return 'Webkul\Customer\Contracts\Customer';
    }


    public function haveActiveOrders($k)
    {
        return $k->orders->pluck('status')->contains(function ($val) {
            return $val === 'pending' || $val === 'processing';
        });
    }


    public function getCurrentGroup()
    {
        $k = auth()->guard()->user();

        return $k->group ?? core()->getGuestCustomerGroup();
    }


    public function uploadImages($dat, $k, $type = 'image')
    {
        if (isset($dat[$type])) {
            $request = request();

            foreach ($dat[$type] as $imageId => $image) {
                $file = $type.'.'.$imageId;
                $dir = 'customer/'.$k->id;

                if ($request->hasFile($file)) {
                    if ($k->{$type}) {
                        Storage::delete($k->{$type});
                    }

                    $k->{$type} = $request->file($file)->store($dir);
                    $k->save();
                }
            }
        } else {
            if ($k->{$type}) {
                Storage::delete($k->{$type});
            }

            $k->{$type} = null;
            $k->save();
        }
    }


    public function syncNewRegisteredCustomerInformation($k)
    {

        Order::where('customer_email', $k->email)->update([
            'is_guest'      => 0,
            'customer_id'   => $k->id,
            'customer_type' => \Webkul\Customer\Models\Customer::class,
        ]);


        $orders = Order::where('customer_id', $k->id)->get();


        $orders->each(function ($o) use ($k) {
            $o->addresses()->update([
                'customer_id' => $k->id,
            ]);

            $o->shipments()->update([
                'customer_id'   => $k->id,
                'customer_type' => \Webkul\Customer\Models\Customer::class,
            ]);

            $o->downloadable_link_purchased()->update([
                'customer_id' => $k->id,
            ]);
        });
    }
}
