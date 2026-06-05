<?php

namespace Modules\Product\Entities\Concerns;

use Modules\Support\Money;
use Modules\Product\Entities\ProductPrice;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Adds support for manual per-currency (fixed) prices.
 *
 * When a product (or variant) is flagged with `is_fixed_price`, the price for a
 * given currency is taken from the `product_prices` table as-is, instead of
 * being derived from the default-currency price through the exchange rate. The
 * special-price schedule (`special_price_start` / `special_price_end`) defined
 * on the model is shared by all currencies; only the amounts differ per
 * currency.
 */
trait HasCurrencyPrices
{
    /**
     * The manual per-currency prices for this model.
     */
    public function prices(): MorphMany
    {
        return $this->morphMany(ProductPrice::class, 'priceable');
    }


    /**
     * Get the per-currency price row for the given currency (if any).
     */
    public function fixedPriceRow(string $currency): ?ProductPrice
    {
        return $this->prices->firstWhere('currency', $currency);
    }


    /**
     * Determine whether a fixed price should be used for the given currency.
     */
    public function usesFixedPriceFor(string $currency): bool
    {
        return (bool) $this->is_fixed_price && !is_null($this->fixedPriceRow($currency));
    }


    /**
     * Get the price in the given currency (defaults to the current currency).
     */
    public function priceIn(?string $currency = null): Money
    {
        $currency = $currency ?: currency();

        if ($this->usesFixedPriceFor($currency)) {
            return $this->fixedPriceRow($currency)->price();
        }

        return $this->price->convert($currency);
    }


    /**
     * Get the discounted special price in the given currency.
     */
    public function specialPriceIn(?string $currency = null): Money
    {
        $currency = $currency ?: currency();

        if ($this->usesFixedPriceFor($currency) && $this->fixedPriceRow($currency)->hasSpecialPriceAmount()) {
            return $this->fixedPriceRow($currency)->specialPrice();
        }

        return $this->getSpecialPrice()->convert($currency);
    }


    /**
     * Determine whether an active special price exists for the given currency.
     */
    public function hasSpecialPriceIn(?string $currency = null): bool
    {
        $currency = $currency ?: currency();

        if ($this->usesFixedPriceFor($currency)) {
            if (!$this->fixedPriceRow($currency)->hasSpecialPriceAmount()) {
                return false;
            }

            return $this->fixedSpecialPriceScheduleIsActive();
        }

        return $this->hasSpecialPrice();
    }


    /**
     * Get the selling price (special price when active, otherwise price) in the
     * given currency.
     */
    public function sellingPriceIn(?string $currency = null): Money
    {
        $currency = $currency ?: currency();

        if ($this->usesFixedPriceFor($currency)) {
            return $this->hasSpecialPriceIn($currency)
                ? $this->specialPriceIn($currency)
                : $this->priceIn($currency);
        }

        return $this->selling_price->convert($currency);
    }


    /**
     * Get the selling price as a default-currency Money that also carries the
     * fixed current-currency value, for use throughout the cart/checkout flow.
     */
    public function sellingPriceForCart(): Money
    {
        if ($this->is_fixed_price) {
            return $this->selling_price->withCurrentAmount(
                $this->sellingPriceIn(currency())->amount()
            );
        }

        return $this->selling_price;
    }


    /**
     * Persist the given per-currency prices for this model.
     *
     * @param array|null $prices
     */
    public function syncCurrencyPrices($prices): void
    {
        $prices = collect($prices ?? [])->filter(function ($row) {
            return isset($row['currency'])
                && isset($row['price'])
                && $row['price'] !== ''
                && $row['price'] !== null;
        });

        $currencies = $prices->pluck('currency')->all();

        $this->prices()
            ->whereNotIn('currency', empty($currencies) ? [''] : $currencies)
            ->delete();

        foreach ($prices as $row) {
            $this->prices()->updateOrCreate(
                ['currency' => $row['currency']],
                [
                    'price' => $row['price'],
                    'special_price' => ($row['special_price'] ?? '') === '' ? null : $row['special_price'],
                    'special_price_type' => $row['special_price_type'] ?? 'fixed',
                ]
            );
        }
    }


    /**
     * Determine whether the shared special-price schedule is currently active.
     */
    protected function fixedSpecialPriceScheduleIsActive(): bool
    {
        $start = $this->special_price_start;
        $end = $this->special_price_end;

        if (!is_null($start) && !is_null($end)) {
            return today() >= $start && today() <= $end;
        }

        if (!is_null($start)) {
            return today() >= $start;
        }

        if (!is_null($end)) {
            return today() <= $end;
        }

        return true;
    }
}
