<?php

use Modules\Product\Entities\Product;
use Modules\FlashSale\Entities\FlashSale;
use Modules\Product\Entities\ProductVariant;
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
            $sellingPrice = $productOrProductVariant->hasSpecialPrice()
                ? $productOrProductVariant->getSpecialPrice()
                : $productOrProductVariant->price;
            $previousPrice  = VatCalculator::priceIncludingVat($sellingPrice->amount())->convertToCurrentCurrency()->format();
            $flashSalePrice = VatCalculator::priceIncludingVat(FlashSale::pivot($productOrProductVariant)->price->amount())->convertToCurrentCurrency()->format();

            if (is_callable($callback)) {
                return $callback($flashSalePrice, $previousPrice);
            }

            return "<span class='special-price'>{$flashSalePrice}</span> <span class='previous-price'>{$previousPrice}</span>";
        }

        $rawAmount    = $productOrProductVariant->attributes['price'] ?? 0;
        $price        = VatCalculator::priceIncludingVat($rawAmount)->convertToCurrentCurrency()->format();
        $specialPrice = VatCalculator::priceIncludingVat($productOrProductVariant->getSpecialPrice()->amount())->convertToCurrentCurrency()->format();

        if (is_callable($callback)) {
            return $callback($price, $specialPrice);
        }

        if (!$productOrProductVariant->hasSpecialPrice()) {
            return $price;
        }

        return "<span class='special-price'>{$specialPrice}</span> <span class='previous-price'>{$price}</span>";
    }
}
