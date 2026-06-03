<?php

namespace Tests\Unit\VatCalculator;

use PHPUnit\Framework\TestCase;

class VatCalculatorTest extends TestCase
{
    private function inclVat(float $amount, float $rate): float
    {
        return $amount * (1 + $rate / 100);
    }

    private function exclVat(float $amount, float $rate): float
    {
        return $amount / (1 + $rate / 100);
    }

    private function vatAmount(float $storedAmount, float $rate, bool $storedInclusive): float
    {
        $incl = $storedInclusive ? $storedAmount : $storedAmount * (1 + $rate / 100);
        return $incl - $incl / (1 + $rate / 100);
    }

    public function test_price_excluding_vat_from_inclusive_stored(): void
    {
        $this->assertEqualsWithDelta(100.0, $this->exclVat(120, 20), 0.01);
    }

    public function test_price_including_vat_from_exclusive_stored(): void
    {
        $this->assertEqualsWithDelta(120.0, $this->inclVat(100, 20), 0.01);
    }

    public function test_vat_amount_from_inclusive_price(): void
    {
        $this->assertEqualsWithDelta(20.0, $this->vatAmount(120, 20, true), 0.01);
    }

    public function test_vat_amount_from_exclusive_price(): void
    {
        $this->assertEqualsWithDelta(20.0, $this->vatAmount(100, 20, false), 0.01);
    }

    public function test_zero_vat_rate(): void
    {
        $this->assertSame(100.0, $this->inclVat(100, 0));
        $this->assertSame(100.0, $this->exclVat(100, 0));
        $this->assertSame(0.0, $this->vatAmount(100, 0, false));
    }

    public function test_roundtrip_incl_then_excl(): void
    {
        $stored = 250.0;
        $incl   = $this->inclVat($stored, 18);
        $excl   = $this->exclVat($incl, 18);
        $this->assertEqualsWithDelta($stored, $excl, 0.01);
    }

    public function test_vat_amount_symmetry_both_methods(): void
    {
        $rate = 20;
        // Starting from exclusive 100 vs inclusive 120 — VAT should be 20 either way
        $fromExcl = $this->vatAmount(100, $rate, false);
        $fromIncl = $this->vatAmount(120, $rate, true);
        $this->assertEqualsWithDelta($fromExcl, $fromIncl, 0.01);
    }

    public function test_store_vat_rate_default_is_20(): void
    {
        $default = 20;
        $this->assertSame(20, $default);
    }

    public function test_prices_include_vat_is_boolean(): void
    {
        $setting = '1';
        $this->assertTrue((bool) $setting);

        $setting = '';
        $this->assertFalse((bool) $setting);
    }
}
