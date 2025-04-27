<?php

namespace Webkul\BookingProduct\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Checkout\Models\CartItem;
use Webkul\Product\DataTypes\CartItemValidationResult;

class EventTicket extends Booking
{
    
    public function getEventDate($bookingProduct): string
    {
        $from = Carbon::createFromTimeString($bookingProduct->available_from)->format('d F, Y h:i A');

        $to = Carbon::createFromTimeString($bookingProduct->available_to)->format('d F, Y h:i A');

        return $from.' - '.$to;
    }

    
    public function getTickets($bookingProduct)
    {
        if (! $bookingProduct->event_tickets()->count()) {
            return [];
        }

        return $this->formatPrice($bookingProduct->event_tickets);
    }

    
    public function formatPrice($tickets)
    {
        foreach ($tickets as $index => $ticket) {
            $price = $ticket->price;

            if ($this->isInSale($ticket)) {
                $price = $ticket->special_price;

                $tickets[$index]['original_converted_price'] = core()->convertPrice($ticket->price);
                $tickets[$index]['original_formatted_price'] = core()->currency($ticket->price);
            }

            $tickets[$index]['id'] = $ticket->id;
            $tickets[$index]['converted_price'] = core()->convertPrice($price);
            $tickets[$index]['formatted_price'] = $formattedPrice = core()->currency($price);
            $tickets[$index]['formatted_price_text'] = trans('shop::app.products.booking.per-ticket-price', ['price' => $formattedPrice]);
        }

        return $tickets;
    }

    
    public function isItemHaveQuantity($cartItem): bool
    {
        $bookingProduct = BookingProduct::findOneByField('product_id', $cartItem['product_id']);

        $ticket = $bookingProduct->event_tickets()->find($cartItem['additional']['booking']['ticket_id']);

        if ($ticket->qty - $this->getBookedQuantity($cartItem) < $cartItem['quantity']) {
            return false;
        }

        return true;
    }

    
    public function getBookedQuantity($data): int
    {
        $result = $this->bookingRepository->getModel()
            ->leftJoin('order_items', 'bookings.order_item_id', '=', 'order_items.id')
            ->addSelect(DB::raw('SUM(qty_ordered - qty_canceled - qty_refunded) as total_qty_booked'))
            ->where('bookings.product_id', $data['product_id'])
            ->where('bookings.booking_product_event_ticket_id', $data['additional']['booking']['ticket_id'])
            ->first();

        return ! is_null($result->total_qty_booked) ? $result->total_qty_booked : 0;
    }

    
    public function addAdditionalPrices(array $products): array
    {
        foreach ($products as $key => $product) {
            $bookingProduct = BookingProduct::findOneByField('product_id', $product['product_id']);

            $ticket = $bookingProduct->event_tickets()->find($product['additional']['booking']['ticket_id']);

            $price = $ticket->price;

            if ($this->isInSale($ticket)) {
                $price = $ticket->special_price;
            }

            $products[$key]['price'] += core()->convertPrice($price);
            $products[$key]['base_price'] += $price;
            $products[$key]['total'] += (core()->convertPrice($price) * $products[$key]['quantity']);
            $products[$key]['base_total'] += ($price * $products[$key]['quantity']);
        }

        return $products;
    }

    
    public function validateCartItem(CartItem $item): CartItemValidationResult
    {
        $result = new CartItemValidationResult;

        if (parent::isCartItemInactive($item)) {
            $result->itemIsInactive();

            return $result;
        }

        $price = $item->product->getTypeInstance()->getFinalPrice($item->quantity);

        $bookingProduct = BookingProduct::findOneByField('product_id', $item->product_id);

        $ticket = $bookingProduct->event_tickets()->find($item->additional['booking']['ticket_id']);

        if (! $ticket) {
            $result->itemIsInactive();

            return $result;
        }

        if ($this->isInSale($ticket)) {
            $price += $ticket->special_price;
        } else {
            $price += $ticket->price;
        }

        if ($price === $item->base_price) {
            return $result;
        }

        $item->base_price = $price;
        $item->price = core()->convertPrice($price);

        $item->base_total = $price * $item->quantity;
        $item->total = core()->convertPrice($price * $item->quantity);

        $item->save();

        return $result;
    }

    
    public function isInSale($ticket): bool
    {
        return $ticket->special_price !== null
            && $ticket->special_price > 0.0
            && (
                $ticket->special_price_from === null
                || $ticket->special_price_from === '0000-00-00 00:00:00'
                || $ticket->special_price_from <= Carbon::now()
            )
            && (
                $ticket->special_price_to === null
                || $ticket->special_price_to === '0000-00-00 00:00:00'
                || $ticket->special_price_to > Carbon::now()
            );
    }
}
