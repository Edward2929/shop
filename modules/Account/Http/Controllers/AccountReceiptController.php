<?php

namespace Modules\Account\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\Order\Entities\Order;
use Modules\Order\Mail\ReceiptUploaded;

class AccountReceiptController
{
    public function store(Request $request, int $id)
    {
        $order = auth()->user()
            ->orders()
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'receipt' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        $file    = $request->file('receipt');
        $ext     = $file->getClientOriginalExtension();
        $stored  = $file->storeAs(
            'receipts',
            $order->id . '_' . time() . '.' . $ext,
            'private'
        );

        $order->update([
            'bank_receipt_path'        => $stored,
            'bank_receipt_uploaded_at' => now(),
            'bank_receipt_status'      => 'pending',
            'status'                   => Order::PENDING_RECEIPT,
        ]);

        $adminEmail = setting('store_email');
        if ($adminEmail) {
            Mail::to($adminEmail)->send(new ReceiptUploaded($order));
        }

        return back()->with('success', trans('storefront::account.receipt.uploaded'));
    }
}
