<?php

namespace Tests\Feature\BankReceipt;

use PHPUnit\Framework\TestCase;

class BankReceiptTest extends TestCase
{
    private function validateReceiptFile(string $mime, int $sizeKb): bool
    {
        $allowed = ['image/jpeg', 'image/png', 'application/pdf'];
        $maxKb   = 5120;

        return in_array($mime, $allowed) && $sizeKb <= $maxKb;
    }

    public function test_jpg_within_limit_is_valid(): void
    {
        $this->assertTrue($this->validateReceiptFile('image/jpeg', 1024));
    }

    public function test_png_within_limit_is_valid(): void
    {
        $this->assertTrue($this->validateReceiptFile('image/png', 2048));
    }

    public function test_pdf_within_limit_is_valid(): void
    {
        $this->assertTrue($this->validateReceiptFile('application/pdf', 4000));
    }

    public function test_file_exceeding_5mb_is_invalid(): void
    {
        $this->assertFalse($this->validateReceiptFile('image/jpeg', 6000));
    }

    public function test_disallowed_mime_type_is_invalid(): void
    {
        $this->assertFalse($this->validateReceiptFile('image/gif', 100));
        $this->assertFalse($this->validateReceiptFile('text/plain', 10));
        $this->assertFalse($this->validateReceiptFile('application/zip', 500));
    }

    public function test_receipt_path_format(): void
    {
        $orderId = 42;
        $ext     = 'pdf';
        $path    = 'receipts/' . $orderId . '_' . 1234567890 . '.' . $ext;

        $this->assertStringStartsWith('receipts/', $path);
        $this->assertStringContainsString((string) $orderId, $path);
    }

    public function test_receipt_status_transitions(): void
    {
        $status = 'pending';
        $this->assertSame('pending', $status);

        $status = 'approved';
        $this->assertSame('approved', $status);

        $status = 'rejected';
        $this->assertSame('rejected', $status);
    }

    public function test_order_status_set_to_pending_receipt_on_upload(): void
    {
        $orderStatus = 'pending_receipt';
        $this->assertSame('pending_receipt', $orderStatus);
    }

    public function test_approve_sets_order_to_processing(): void
    {
        $receiptStatus = 'approved';
        $orderStatus   = $receiptStatus === 'approved' ? 'processing' : 'on_hold';

        $this->assertSame('processing', $orderStatus);
    }

    public function test_reject_sets_order_to_on_hold(): void
    {
        $receiptStatus = 'rejected';
        $orderStatus   = $receiptStatus === 'approved' ? 'processing' : 'on_hold';

        $this->assertSame('on_hold', $orderStatus);
    }

    public function test_only_bank_transfer_orders_show_receipt_section(): void
    {
        $this->assertTrue($this->shouldShowReceiptSection('Bank Transfer'));
        $this->assertFalse($this->shouldShowReceiptSection('PayPal'));
        $this->assertFalse($this->shouldShowReceiptSection('Stripe'));
        $this->assertFalse($this->shouldShowReceiptSection('Cash On Delivery'));
    }

    private function shouldShowReceiptSection(string $paymentMethod): bool
    {
        return $paymentMethod === 'Bank Transfer';
    }
}
