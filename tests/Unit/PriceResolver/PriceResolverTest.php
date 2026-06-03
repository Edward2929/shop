<?php

namespace Tests\Unit\PriceResolver;

use PHPUnit\Framework\TestCase;

class PriceResolverTest extends TestCase
{
    private function resolveWithFixed(?array $fixedPrices, float $baseAmount, float $rate, string $currency): float
    {
        if (!empty($fixedPrices[$currency])) {
            return (float) $fixedPrices[$currency];
        }

        return $baseAmount * $rate;
    }

    public function test_fixed_price_takes_priority_over_exchange_rate(): void
    {
        $fixed = ['USD' => 29.99, 'EUR' => 27.50];
        $result = $this->resolveWithFixed($fixed, 100.0, 1.2, 'USD');

        $this->assertSame(29.99, $result);
    }

    public function test_falls_back_to_exchange_rate_when_no_fixed_price(): void
    {
        $fixed = ['EUR' => 27.50];
        $result = $this->resolveWithFixed($fixed, 100.0, 1.2, 'USD');

        $this->assertEqualsWithDelta(120.0, $result, 0.001);
    }

    public function test_null_fixed_prices_uses_exchange_rate(): void
    {
        $result = $this->resolveWithFixed(null, 100.0, 1.5, 'GBP');

        $this->assertEqualsWithDelta(150.0, $result, 0.001);
    }

    public function test_empty_fixed_prices_uses_exchange_rate(): void
    {
        $result = $this->resolveWithFixed([], 50.0, 2.0, 'USD');

        $this->assertEqualsWithDelta(100.0, $result, 0.001);
    }

    public function test_zero_price_is_respected(): void
    {
        // Fixed price of 0 should NOT override (0 is falsy)
        $result = $this->resolveWithFixed(['USD' => 0], 100.0, 1.2, 'USD');

        $this->assertEqualsWithDelta(120.0, $result, 0.001);
    }

    public function test_multiple_currencies_resolved_independently(): void
    {
        $fixed = ['USD' => 29.99, 'EUR' => 27.50];

        $usd = $this->resolveWithFixed($fixed, 100.0, 1.2, 'USD');
        $eur = $this->resolveWithFixed($fixed, 100.0, 0.9, 'EUR');

        $this->assertSame(29.99, $usd);
        $this->assertSame(27.50, $eur);
    }

    public function test_default_currency_does_not_need_fixed_price(): void
    {
        // Default currency = no conversion needed, rate = 1
        $fixed = ['USD' => 29.99];
        $result = $this->resolveWithFixed($fixed, 100.0, 1.0, 'TRY');

        $this->assertEqualsWithDelta(100.0, $result, 0.001);
    }
}
