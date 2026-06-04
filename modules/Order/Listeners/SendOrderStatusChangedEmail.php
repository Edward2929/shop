<?php

namespace Modules\Order\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Order\Events\OrderStatusChanged;
use Modules\Order\Mail\OrderStatusChanged as OrderStatusChangedEmail;

class SendOrderStatusChangedEmail
{
    /**
     * Handle the event.
     *
     * @param OrderStatusChanged $event
     *
     * @return void
     */
    public function handle(OrderStatusChanged $event)
    {
        if (!in_array($event->order->status, setting('email_order_statuses', []))) {
            return;
        }

        try {
            Mail::to($event->order->customer_email)
                ->send(new OrderStatusChangedEmail($event->order));
        } catch (\Exception $e) {
            Log::error('Order status changed notification failed: ' . $e->getMessage());
        }
    }
}
