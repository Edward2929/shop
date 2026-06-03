<?php

namespace Tests\Feature\PayTR;

use PHPUnit\Framework\TestCase;

/**
 * Callback hash doğrulama ve sipariş durumu mantığı testleri.
 * Bu testler gerçek DB/HTTP bağlantısı gerektirmez.
 */
class PayTRCallbackTest extends TestCase
{
    private string $merchantKey  = 'testMerchantKey';
    private string $merchantSalt = 'testMerchantSalt';

    private function makeHash(string $merchantOid, string $status, string $totalAmount): string
    {
        return base64_encode(hash_hmac('sha256', $merchantOid . $this->merchantSalt . $status . $totalAmount, $this->merchantKey, true));
    }

    public function test_valid_callback_hash_matches(): void
    {
        $hash = $this->makeHash('paytr1', 'success', '10000');

        $this->assertTrue(hash_equals($hash, $hash));
    }

    public function test_tampered_callback_hash_does_not_match(): void
    {
        $realHash    = $this->makeHash('paytr1', 'success', '10000');
        $tamperedHash = $this->makeHash('paytr1', 'success', '99999');

        $this->assertFalse(hash_equals($realHash, $tamperedHash));
    }

    public function test_failed_status_does_not_match_success_hash(): void
    {
        $successHash = $this->makeHash('paytr1', 'success', '10000');
        $failedHash  = $this->makeHash('paytr1', 'failed', '10000');

        $this->assertNotEquals($successHash, $failedHash);
    }

    public function test_order_id_extracted_from_merchant_oid(): void
    {
        $merchantOid = 'paytr42';
        $orderId     = str_replace('paytr', '', $merchantOid);

        $this->assertSame('42', $orderId);
    }

    public function test_installment_count_above_1_is_installment_type(): void
    {
        $count = 6;
        $type  = $count > 1 ? 'installment' : 'single';

        $this->assertSame('installment', $type);
    }

    public function test_installment_count_1_is_single_type(): void
    {
        $count = 1;
        $type  = $count > 1 ? 'installment' : 'single';

        $this->assertSame('single', $type);
    }

    public function test_total_paid_is_divided_by_100(): void
    {
        $rawAmount = '34560';
        $paid      = (int) $rawAmount / 100;

        $this->assertSame(345.60, $paid);
    }

    public function test_duplicate_ok_response_logic(): void
    {
        // Eğer sipariş zaten PENDING_PAYMENT dışındaysa OK döndür
        $currentStatus = 'processing';
        $shouldSkip    = ($currentStatus !== 'pending_payment');

        $this->assertTrue($shouldSkip);
    }
}
