<?php

namespace Modules\Order\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Modules\Media\Entities\File;
use Modules\Order\Entities\Order;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ReceiptUploaded extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $heading;
    public $text;

    public function __construct(public Order $order)
    {
        $this->heading = trans('order::mail.receipt_uploaded_heading');
        $this->text    = trans('order::mail.receipt_uploaded_text', ['order_id' => $order->id]);
    }

    public function build(): static
    {
        return $this->subject(trans('order::mail.receipt_uploaded_subject', ['order_id' => $this->order->id]))
            ->view('storefront::emails.' . $this->getViewName(), [
                'logo' => File::findOrNew(setting('storefront_mail_logo'))->path,
            ]);
    }

    private function getViewName(): string
    {
        return 'text' . (is_rtl() ? '_rtl' : '');
    }
}
