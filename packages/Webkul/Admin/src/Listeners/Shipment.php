<?php

namespace Webkul\Admin\Listeners;

use Webkul\Admin\Mail\Order\InventorySourceNotification;
use Webkul\Admin\Mail\Order\ShippedNotification;
use Webkul\Sales\Contracts\Shipment as ShipmentContract;

class Shipment extends Base
{
    
    public function afterCreated(ShipmentContract $shipment)
    {
        try {
            if (core()->getConfigData('emails.general.notifications.emails.general.notifications.new_shipment_mail_to_admin')) {
                $this->prepareMail($shipment, new ShippedNotification($shipment));
            }

            if (core()->getConfigData('emails.general.notifications.emails.general.notifications.new_inventory_source')) {
                $this->prepareMail($shipment, new InventorySourceNotification($shipment));
            }
        } catch (\Exception $e) {
            report($e);
        }
    }
}
