<?php

namespace Webkul\BookingProduct\Listeners;

use Webkul\BookingProduct\Repositories\BookingRepository;

class Order
{
    
    public function __construct(protected BookingRepository $bookingRepository) {}

    
    public function afterPlaceOrder($o)
    {
        $this->bookingRepository->create(['order' => $o]);
    }
}
