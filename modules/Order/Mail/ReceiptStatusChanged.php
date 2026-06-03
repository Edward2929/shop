<?php

namespace Modules\Order\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Modules\Media\Entities\File;
use Modules\Order\Entities\Order;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ReceiptStatusChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $heading;
    public $text;

    public function __construct(public Order $order)
    {
        app()->setLocale($order->locale);

        $approved = $order->bank_receipt_status === 'approved';

        $this->heading = trans('storefront::mail.hello', ['name' => $order->customer_first_name]);
        $this->text    = $approved
            ? trans('order::mail.receipt_approved_text', ['order_id' => $order->id])
            : trans('order::mail.receipt_rejected_text', [
                'order_id' => $order->id,
                'note'     => $order->bank_receipt_admin_note ?? '',
            ]);
    }

    public function build(): static
    {
        $approved = $this->order->bank_receipt_status === 'approved';

        return $this->subject(
            $approved
                ? trans('order::mail.receipt_approved_subject')
                : trans('order::mail.receipt_rejected_subject')
        )
            ->view('storefront::emails.' . $this->getViewName(), [
                'logo' => File::findOrNew(setting('storefront_mail_logo'))->path,
            ]);
    }

    private function getViewName(): string
    {
        return 'text' . (is_rtl() ? '_rtl' : '');
    }
}
