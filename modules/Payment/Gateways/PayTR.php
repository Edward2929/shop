<?php

namespace Modules\Payment\Gateways;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\Order\Entities\Order;
use Modules\Payment\GatewayInterface;
use Modules\Payment\Responses\PayTRResponse;

class PayTR implements GatewayInterface
{
    public $label;
    public $description;

    const IFRAME_TOKEN_URL = 'https://www.paytr.com/odeme/api/get-token';
    const DIRECT_PAY_URL   = 'https://www.paytr.com/odeme';
    const BIN_QUERY_URL    = 'https://www.paytr.com/odeme/api/bin-detail';
    const TAKSIT_QUERY_URL = 'https://www.paytr.com/odeme/taksit-oranlari';

    public function __construct()
    {
        $this->label       = setting('paytr_label');
        $this->description = setting('paytr_description');
    }

    public function purchase(Order $order, Request $request): PayTRResponse
    {
        if ($this->mode() === 'direct') {
            return $this->purchaseDirect($order, $request);
        }

        return $this->purchaseIframe($order, $request);
    }

    private function purchaseIframe(Order $order, Request $request): PayTRResponse
    {
        $merchantOid = $this->makeMerchantOid($order);
        $order->update(['paytr_merchant_oid' => $merchantOid]);

        $merchantId   = setting('paytr_merchant_id');
        $merchantKey  = setting('paytr_merchant_key');
        $merchantSalt = setting('paytr_merchant_salt');
        $userIp       = $request->ip();
        $email        = $order->customer_email;
        $amount       = (int) round($order->total->convertToCurrentCurrency()->amount() * 100);
        $currency     = $this->normalizeCurrency();
        $userBasket   = $this->prepareUserBasket($order);
        $noInstall    = setting('paytr_installment_enabled') ? 0 : 1;
        $maxInstall   = (int) (setting('paytr_max_installment') ?: 0);
        $testMode     = setting('paytr_test_mode') ? 1 : 0;

        $hashStr   = $merchantId . $userIp . $merchantOid . $email . $amount . $currency . $noInstall . $maxInstall . $userBasket . $testMode;
        $paytrToken = base64_encode(hash_hmac('sha256', $hashStr . $merchantSalt, $merchantKey, true));

        $result = Http::asForm()->post(self::IFRAME_TOKEN_URL, [
            'merchant_id'     => $merchantId,
            'user_ip'         => $userIp,
            'merchant_oid'    => $merchantOid,
            'email'           => $email,
            'payment_amount'  => $amount,
            'currency'        => $currency,
            'user_basket'     => $userBasket,
            'no_installment'  => $noInstall,
            'max_installment' => $maxInstall,
            'paytr_token'     => $paytrToken,
            'user_name'       => $this->getUserName($order),
            'user_address'    => $this->getUserAddress($order),
            'user_phone'      => $order->customer_phone ?: '05000000000',
            'merchant_ok_url' => $this->okUrl($order),
            'merchant_fail_url' => $this->failUrl($order),
            'test_mode'       => $testMode,
            'debug_on'        => 1,
            'lang'            => $this->lang(),
            'timeout_limit'   => 30,
        ])->json();

        if (($result['status'] ?? '') !== 'success') {
            throw new Exception($result['reason'] ?? trans('core::messages.something_went_wrong'));
        }

        return new PayTRResponse($order, ['mode' => 'iframe', 'iframeToken' => $result['token']]);
    }

    private function purchaseDirect(Order $order, Request $request): PayTRResponse
    {
        $merchantOid      = $this->makeMerchantOid($order);
        $installmentCount = (int) $request->input('paytr_installment_count', 0);

        $order->update([
            'paytr_merchant_oid'    => $merchantOid,
            'paytr_installment_count' => $installmentCount,
        ]);

        $merchantId   = setting('paytr_merchant_id');
        $merchantKey  = setting('paytr_merchant_key');
        $merchantSalt = setting('paytr_merchant_salt');
        $userIp       = $request->ip();
        $email        = $order->customer_email;
        $amount       = number_format($order->total->convertToCurrentCurrency()->amount(), 2, '.', '');
        $currency     = $this->normalizeCurrency();
        $paymentType  = 'card';
        $testMode     = setting('paytr_test_mode') ? 1 : 0;
        $non3d        = 0;
        $userBasket   = $this->prepareUserBasket($order);

        $hashStr    = $merchantId . $userIp . $merchantOid . $email . $amount . $paymentType . $installmentCount . $currency . $testMode . $non3d;
        $paytrToken = base64_encode(hash_hmac('sha256', $hashStr . $merchantSalt, $merchantKey, true));

        $formFields = [
            'merchant_id'       => $merchantId,
            'paytr_token'       => $paytrToken,
            'user_ip'           => $userIp,
            'merchant_oid'      => $merchantOid,
            'email'             => $email,
            'payment_type'      => $paymentType,
            'payment_amount'    => $amount,
            'installment_count' => $installmentCount,
            'currency'          => $currency,
            'test_mode'         => $testMode,
            'non_3d'            => $non3d,
            'merchant_ok_url'   => $this->okUrl($order),
            'merchant_fail_url' => $this->failUrl($order),
            'user_name'         => $this->getUserName($order),
            'user_address'      => $this->getUserAddress($order),
            'user_phone'        => $order->customer_phone ?: '05000000000',
            'user_basket'       => $userBasket,
            'debug_on'          => 1,
        ];

        return new PayTRResponse($order, ['mode' => 'direct', 'formFields' => $formFields, 'payUrl' => self::DIRECT_PAY_URL]);
    }

    public function complete(Order $order): PayTRResponse
    {
        return new PayTRResponse($order, ['mode' => $this->mode()]);
    }

    // -------------------------------------------------------------------
    // Static helpers (used by controller / AJAX endpoints)
    // -------------------------------------------------------------------

    public static function queryBin(string $binNumber): array
    {
        $merchantId   = setting('paytr_merchant_id');
        $merchantKey  = setting('paytr_merchant_key');
        $merchantSalt = setting('paytr_merchant_salt');

        $paytrToken = base64_encode(hash_hmac('sha256', $merchantId . $binNumber . $merchantSalt, $merchantKey, true));

        return Http::asForm()->post(self::BIN_QUERY_URL, [
            'merchant_id' => $merchantId,
            'bin_number'  => $binNumber,
            'paytr_token' => $paytrToken,
        ])->json() ?: ['status' => 'error'];
    }

    public static function queryInstallmentRates(): array
    {
        $merchantId   = setting('paytr_merchant_id');
        $merchantKey  = setting('paytr_merchant_key');
        $merchantSalt = setting('paytr_merchant_salt');
        $requestId    = uniqid('inst_');

        $paytrToken = base64_encode(hash_hmac('sha256', $merchantId . $requestId . $merchantSalt, $merchantKey, true));

        return Http::asForm()->post(self::TAKSIT_QUERY_URL, [
            'merchant_id' => $merchantId,
            'request_id'  => $requestId,
            'paytr_token' => $paytrToken,
        ])->json() ?: ['status' => 'error'];
    }

    public static function verifyCallbackHash(string $merchantOid, string $status, string $totalAmount): bool
    {
        $merchantKey  = setting('paytr_merchant_key');
        $merchantSalt = setting('paytr_merchant_salt');

        $expected = base64_encode(hash_hmac('sha256', $merchantOid . $merchantSalt . $status . $totalAmount, $merchantKey, true));

        return hash_equals($expected, request()->input('hash', ''));
    }

    // -------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------

    private function prepareUserBasket(Order $order): string
    {
        $basket = $order->products->map(fn($p) => [
            $p->product->name,
            number_format($p->unit_price->convertToCurrentCurrency()->amount(), 2, '.', ''),
            $p->qty,
        ])->values()->toArray();

        return base64_encode(json_encode($basket));
    }

    private function makeMerchantOid(Order $order): string
    {
        return 'paytr' . $order->id;
    }

    private function getUserName(Order $order): string
    {
        return trim($order->customer_first_name . ' ' . $order->customer_last_name);
    }

    private function getUserAddress(Order $order): string
    {
        return implode(', ', array_filter([
            $order->billing_address_1,
            $order->billing_address_2,
            $order->billing_city,
        ]));
    }

    private function normalizeCurrency(): string
    {
        $map = ['TRY' => 'TL'];
        $c   = strtoupper(currency());
        return $map[$c] ?? $c;
    }

    private function lang(): string
    {
        return locale() === 'tr' ? 'tr' : 'en';
    }

    private function okUrl(Order $order): string
    {
        return route('checkout.complete.store', ['orderId' => $order->id, 'paymentMethod' => 'paytr']);
    }

    private function failUrl(Order $order): string
    {
        return route('checkout.payment_canceled.store', ['orderId' => $order->id, 'paymentMethod' => 'paytr']);
    }

    public function mode(): string
    {
        return setting('paytr_mode') ?: 'iframe';
    }
}
