<?php

namespace Webkul\BookingProduct\Helpers;

class AppointmentSlot extends Booking
{
    
    public function haveSufficientQuantity(int $qty, $bookingProduct): bool
    {
        return true;
    }
}
