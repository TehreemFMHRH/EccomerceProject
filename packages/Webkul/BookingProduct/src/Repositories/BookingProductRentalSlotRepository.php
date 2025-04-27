<?php

namespace Webkul\BookingProduct\Repositories;

use Webkul\BookingProduct\Contracts\BookingProductRentalSlot;
use Webkul\Core\Eloquent\Repository;

class BookingProductRentalSlotRepository extends Repository
{
    
    public function model(): string
    {
        return BookingProductRentalSlot::class;
    }
}
