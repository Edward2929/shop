<?php

namespace Modules\Support\Services;

use Modules\Support\Money;

class VatCalculator
{
    /**
     * Return the VAT rate from settings (e.g. 20 for 20%).
     */
    public static function rate(): float
    {
        return (float) (setting('store_vat_rate') ?? 20);
    }

    /**
     * Whether prices in DB are entered VAT-inclusive.
     */
    public static function pricesIncludeVat(): bool
    {
        return (bool) setting('prices_include_vat');
    }

    /**
     * Given the stored (raw) price amount, return price WITHOUT VAT.
     */
    public static function excludingVat(float|int|null $amount): float
    {
        $amount = (float) ($amount ?? 0);

        if (static::pricesIncludeVat()) {
            return $amount / (1 + static::rate() / 100);
        }

        return $amount;
    }

    /**
     * Given the stored (raw) price amount, return price WITH VAT.
     */
    public static function includingVat(float|int|null $amount): float
    {
        $amount = (float) ($amount ?? 0);

        if (!static::pricesIncludeVat()) {
            return $amount * (1 + static::rate() / 100);
        }

        return $amount;
    }

    /**
     * Return the VAT amount for a given stored price.
     */
    public static function vatAmount(float|int|null $amount): float
    {
        return static::includingVat($amount) - static::excludingVat($amount);
    }

    /**
     * Return a Money object (in default currency) for the price WITHOUT VAT.
     */
    public static function priceExcludingVat(float|int|null $storedAmount): Money
    {
        return Money::inDefaultCurrency(static::excludingVat($storedAmount));
    }

    /**
     * Return a Money object (in default currency) for the price WITH VAT.
     */
    public static function priceIncludingVat(float|int|null $storedAmount): Money
    {
        return Money::inDefaultCurrency(static::includingVat($storedAmount));
    }

    /**
     * Return a Money object (in default currency) for the VAT component.
     */
    public static function vatMoney(float|int|null $storedAmount): Money
    {
        return Money::inDefaultCurrency(static::vatAmount($storedAmount));
    }
}
