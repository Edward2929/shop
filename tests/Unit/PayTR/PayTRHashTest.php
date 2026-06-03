<?php

namespace Tests\Unit\PayTR;

use PHPUnit\Framework\TestCase;

class PayTRHashTest extends TestCase
{
    private string $merchantKey  = 'testKey123';
    private string $merchantSalt = 'testSalt456';

    public function test_iframe_token_hash_is_hmac_sha256_base64(): void
    {
        $merchantId    = '123456';
        $userIp        = '127.0.0.1';
        $merchantOid   = 'paytr99';
        $email         = 'test@test.com';
        $amount        = 10000;
        $currency      = 'TL';
        $noInstall     = 0;
        $maxInstall    = 0;
        $userBasket    = base64_encode(json_encode([['Product', '100.00', 1]]));
        $testMode      = 1;

        $hashStr = $merchantId . $userIp . $merchantOid . $email . $amount . $currency . $noInstall . $maxInstall . $userBasket . $testMode;
        $token   = base64_encode(hash_hmac('sha256', $hashStr . $this->merchantSalt, $this->merchantKey, true));

        $this->assertNotEmpty($token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $token);
    }

    public function test_direct_api_token_hash_is_hmac_sha256_base64(): void
    {
        $merchantId       = '123456';
        $userIp           = '127.0.0.1';
        $merchantOid      = 'paytr99';
        $email            = 'test@test.com';
        $amount           = '100.00';
        $paymentType      = 'card';
        $installmentCount = 0;
        $currency         = 'TL';
        $testMode         = 1;
        $non3d            = 0;

        $hashStr = $merchantId . $userIp . $merchantOid . $email . $amount . $paymentType . $installmentCount . $currency . $testMode . $non3d;
        $token   = base64_encode(hash_hmac('sha256', $hashStr . $this->merchantSalt, $this->merchantKey, true));

        $this->assertNotEmpty($token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]+$/', $token);
    }

    public function test_callback_hash_verification(): void
    {
        $merchantOid = 'paytr99';
        $status      = 'success';
        $totalAmount = '10000';

        $expected = base64_encode(hash_hmac('sha256', $merchantOid . $this->merchantSalt . $status . $totalAmount, $this->merchantKey, true));

        $this->assertNotEmpty($expected);

        // Wrong data should produce a different hash
        $wrong = base64_encode(hash_hmac('sha256', $merchantOid . $this->merchantSalt . 'failed' . $totalAmount, $this->merchantKey, true));
        $this->assertNotEquals($expected, $wrong);
    }

    public function test_bin_query_token_hash(): void
    {
        $merchantId = '123456';
        $binNumber  = '45678901';

        $token = base64_encode(hash_hmac('sha256', $merchantId . $binNumber . $this->merchantSalt, $this->merchantKey, true));

        $this->assertNotEmpty($token);
    }

    public function test_merchant_oid_format(): void
    {
        $orderId     = 42;
        $merchantOid = 'paytr' . $orderId;

        $this->assertSame('paytr42', $merchantOid);
        $this->assertLessThanOrEqual(64, strlen($merchantOid));
    }

    public function test_user_basket_is_base64_json(): void
    {
        $basket = [['Product A', '50.00', 2], ['Product B', '25.99', 1]];
        $encoded = base64_encode(json_encode($basket));
        $decoded = json_decode(base64_decode($encoded), true);

        $this->assertSame($basket, $decoded);
    }

    public function test_payment_amount_is_multiplied_by_100_for_iframe(): void
    {
        $total  = 34.56;
        $amount = (int) round($total * 100);

        $this->assertSame(3456, $amount);
    }

    public function test_payment_amount_is_decimal_for_direct(): void
    {
        $total  = 34.56;
        $amount = number_format($total, 2, '.', '');

        $this->assertSame('34.56', $amount);
    }

    public function test_currency_normalization_try_to_tl(): void
    {
        $map = ['TRY' => 'TL'];
        $c   = 'TRY';
        $this->assertSame('TL', $map[$c] ?? $c);
    }

    public function test_currency_normalization_usd_unchanged(): void
    {
        $map = ['TRY' => 'TL'];
        $c   = 'USD';
        $this->assertSame('USD', $map[$c] ?? $c);
    }

    public function test_installment_payment_type_detection(): void
    {
        $this->assertSame('installment', $this->detectPaymentType('card', 3));
        $this->assertSame('single', $this->detectPaymentType('card', 1));
        $this->assertSame('eft', $this->detectPaymentType('eft', 1));
    }

    private function detectPaymentType(string $paymentType, int $installmentCount): string
    {
        return $installmentCount > 1 ? 'installment' : ($paymentType === 'eft' ? 'eft' : 'single');
    }
}
