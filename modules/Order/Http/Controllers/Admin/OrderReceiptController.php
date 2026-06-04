<?php

namespace Modules\Order\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\Order\Entities\Order;
use Modules\Order\Mail\ReceiptStatusChanged;

class OrderReceiptController
{
    public function download(Order $order)
    {
        abort_unless($order->bank_receipt_path, 404);
        abort_unless(Storage::disk('private')->exists($order->bank_receipt_path), 404);

        return Storage::disk('private')->download($order->bank_receipt_path);
    }

    public function approve(Request $request, Order $order)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        abort_unless($order->bank_receipt_path, 422);

        $order->update([
            'bank_receipt_status'     => 'approved',
            'bank_receipt_admin_note' => $request->input('admin_note'),
            'status'                  => Order::PROCESSING,
        ]);

        try {
            Mail::to($order->customer_email)->send(new ReceiptStatusChanged($order));
        } catch (\Exception $e) {
            Log::error('Receipt approved notification failed: ' . $e->getMessage());
        }

        return response()->json(['message' => trans('order::orders.receipt_approved')]);
    }

    public function reject(Request $request, Order $order)
    {
        $request->validate([
            'admin_note' => 'nullable|string|max:1000',
        ]);

        abort_unless($order->bank_receipt_path, 422);

        $order->update([
            'bank_receipt_status'     => 'rejected',
            'bank_receipt_admin_note' => $request->input('admin_note'),
            'status'                  => Order::ON_HOLD,
        ]);

        try {
            Mail::to($order->customer_email)->send(new ReceiptStatusChanged($order));
        } catch (\Exception $e) {
            Log::error('Receipt rejected notification failed: ' . $e->getMessage());
        }

        return response()->json(['message' => trans('order::orders.receipt_rejected')]);
    }
}
