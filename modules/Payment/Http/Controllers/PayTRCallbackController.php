<?php

namespace Modules\Payment\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Order\Entities\Order;
use Modules\Payment\Gateways\PayTR;
use Modules\Checkout\Events\OrderPlaced;

class PayTRCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $merchantOid = $request->input('merchant_oid');
        $status      = $request->input('status');
        $totalAmount = $request->input('total_amount');

        if (!PayTR::verifyCallbackHash($merchantOid, $status, $totalAmount)) {
            return response('HASH_ERROR', 400);
        }

        $orderId = str_replace('paytr', '', $merchantOid);
        $order   = Order::find($orderId);

        if (!$order) {
            return response('OK');
        }

        // Idempotency: skip if already processed
        if ($order->status !== Order::PENDING_PAYMENT) {
            return response('OK');
        }

        $installmentCount   = (int) $request->input('installment_count', 1);
        $paymentType        = $request->input('payment_type', 'card');
        $totalPaid          = $totalAmount / 100;
        $installmentAmount  = $installmentCount > 1
            ? round($totalPaid / $installmentCount, 2)
            : null;

        $order->update([
            'paytr_payment_type'       => $installmentCount > 1 ? 'installment' : ($paymentType === 'eft' ? 'eft' : 'single'),
            'paytr_installment_count'  => $installmentCount,
            'paytr_installment_amount' => $installmentAmount,
            'paytr_total_paid'         => $totalPaid,
            'paytr_status'             => $status,
            'paytr_raw_response'       => json_encode($request->all()),
        ]);

        if ($status === 'success') {
            $order->update(['status' => Order::PROCESSING]);
            event(new OrderPlaced($order));
        } else {
            $order->update(['status' => Order::CANCELED]);
        }

        return response('OK');
    }

    public function binQuery(Request $request)
    {
        $request->validate(['bin_number' => 'required|string|min:6|max:8']);

        return response()->json(PayTR::queryBin($request->input('bin_number')));
    }

    public function installmentRates()
    {
        return response()->json(PayTR::queryInstallmentRates());
    }
}
