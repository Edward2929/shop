<?php

namespace Modules\Support\Services;

use Modules\Support\Money;
use Modules\Currency\Entities\CurrencyRate;

class PriceResolver
{
    /**
     * Resolve the price for the given product/variant in the given currency.
     * Returns a fixed price Money object when one is set, otherwise falls back
     * to exchange-rate conversion from the stored default-currency amount.
     *
     * @param  array|null  $fixedPrices  Associative array: ['USD' => 29.99, ...]
     * @param  float       $baseAmount   Raw stored amount in default currency
     * @param  string      $currency     Target currency code
     */
    public static function resolve(?array $fixedPrices, float $baseAmount, string $currency): Money
    {
        if (!empty($fixedPrices[$currency])) {
            return new Money((float) $fixedPrices[$currency], $currency);
        }

        $rate = CurrencyRate::for($currency);

        return new Money($baseAmount * $rate, $currency);
    }

    /**
     * Resolve price for the current session currency.
     */
    public static function forCurrentCurrency(?array $fixedPrices, float $baseAmount): Money
    {
        return static::resolve($fixedPrices, $baseAmount, currency());
    }
}
