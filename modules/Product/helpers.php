<?php

use Modules\Product\Entities\Product;
use Modules\FlashSale\Entities\FlashSale;
use Modules\Product\Entities\ProductVariant;
use Modules\Support\ProductMoney;
use Modules\Support\Services\VatCalculator;

if (!function_exists('product_price_formatted')) {
    /**
     * Get the selling price of the given product — always VAT-inclusive for listings.
     *
     * @param Product|ProductVariant $productOrProductVariant
     * @param Closure|null $callback
     *
     * @return string
     */
    function product_price_formatted(Product|ProductVariant $productOrProductVariant, Closure $callback = null): string
    {
        if ($productOrProductVariant instanceof Product && FlashSale::contains($productOrProductVariant)) {
            $flashRawFixed = $productOrProductVariant->attributes['fixed_prices'] ?? null;
            $flashFixedPrices = $flashRawFixed
                ? (is_array($flashRawFixed) ? $flashRawFixed : json_decode($flashRawFixed, true))
                : [];

            $sellingPrice = $productOrProductVariant->hasSpecialPrice()
                ? $productOrProductVariant->getSpecialPrice()
                : $productOrProductVariant->price;
            $previousPrice  = ProductMoney::inDefaultCurrencyWithFixed(
                VatCalculator::includingVat($sellingPrice->amount()),
                $flashFixedPrices
            )->convertToCurrentCurrency()->format();
            $flashSalePrice = VatCalculator::priceIncludingVat(FlashSale::pivot($productOrProductVariant)->price->amount())->convertToCurrentCurrency()->format();

            if (is_callable($callback)) {
                return $callback($flashSalePrice, $previousPrice);
            }

            return "<span class='special-price'>{$flashSalePrice}</span> <span class='previous-price'>{$previousPrice}</span>";
        }

        $rawFixed = $productOrProductVariant->attributes['fixed_prices'] ?? null;
        $fixedPricesArray = $rawFixed
            ? (is_array($rawFixed) ? $rawFixed : json_decode($rawFixed, true))
            : [];

        $rawAmount    = $productOrProductVariant->attributes['price'] ?? 0;
        $vatInclusive = VatCalculator::includingVat((float) $rawAmount);
        $price        = ProductMoney::inDefaultCurrencyWithFixed($vatInclusive, $fixedPricesArray)
            ->convertToCurrentCurrency()
            ->format();

        $specialRaw     = $productOrProductVariant->getSpecialPrice()?->amount() ?? 0;
        $specialVatIncl = VatCalculator::includingVat((float) $specialRaw);
        $specialPrice   = ProductMoney::inDefaultCurrencyWithFixed($specialVatIncl, $fixedPricesArray)
            ->convertToCurrentCurrency()
            ->format();

        if (is_callable($callback)) {
            return $callback($price, $specialPrice);
        }

        if (!$productOrProductVariant->hasSpecialPrice()) {
            return $price;
        }

        return "<span class='special-price'>{$specialPrice}</span> <span class='previous-price'>{$price}</span>";
    }
}
