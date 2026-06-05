<?php

namespace Modules\Product\Entities\Concerns;

use Modules\Support\Money;
use Modules\Media\Entities\File;
use Modules\FlashSale\Entities\FlashSale;
use Illuminate\Database\Eloquent\Collection;
use Modules\FlashSale\Entities\FlashSaleProduct;

trait ModelAccessors
{
    public function getVariantAttribute()
    {
        return $this->variants->where('is_default', 1)->first();
    }


    public function getIsInFlashSaleAttribute()
    {
        return FlashSale::contains($this);
    }


    public function getFlashSaleEndDateAttribute()
    {
        if (FlashSale::contains($this)) {
            return FlashSaleProduct::where('product_id', $this->id)->first()?->end_date;
        }
    }


    public function getPriceAttribute($price): Money
    {
        $money = Money::inDefaultCurrency($price);

        if ($this->is_fixed_price && $this->fixedPriceRow(currency())) {
            return $money->withCurrentAmount($this->priceIn(currency())->amount());
        }

        return $money;
    }


    public function getFormattedPriceAttribute(): string
    {
        return product_price_formatted($this);
    }


    public function getFormattedPriceRangeAttribute(): ?string
    {
        if ($this->variants->count()) {
            $prices = $this->variants->map(function ($variant) {
                return $variant->priceIn(currency())->amount();
            });

            $minPrice = $prices->min();
            $maxPrice = $prices->max();

            if ($minPrice !== $maxPrice) {
                $formattedMinPriceInCurrentCurrency = (new Money($minPrice, currency()))->format();
                $formattedMaxPriceInCurrentCurrency = (new Money($maxPrice, currency()))->format();

                return "$formattedMinPriceInCurrentCurrency - $formattedMaxPriceInCurrentCurrency";
            }
        }

        return null;
    }


    public function getSpecialPriceAttribute($specialPrice)
    {
        if (!is_null($specialPrice)) {
            $money = Money::inDefaultCurrency($specialPrice);

            if ($this->is_fixed_price
                && ($row = $this->fixedPriceRow(currency()))
                && $row->hasSpecialPriceAmount()) {
                return $money->withCurrentAmount($this->specialPriceIn(currency())->amount());
            }

            return $money;
        }
    }


    public function getHasPercentageSpecialPriceAttribute(): bool
    {
        return $this->hasPercentageSpecialPrice();
    }


    public function getSpecialPricePercentAttribute()
    {
        if ($this->hasPercentageSpecialPrice()) {
            return round($this->special_price->amount(), 2);
        }
    }


    public function getSellingPriceAttribute($sellingPrice): Money
    {
        if (FlashSale::contains($this)) {
            return Money::inDefaultCurrency(FlashSale::pivot($this)->price->amount());
        }

        $money = Money::inDefaultCurrency($sellingPrice);

        if ($this->is_fixed_price && $this->fixedPriceRow(currency())) {
            return $money->withCurrentAmount($this->sellingPriceIn(currency())->amount());
        }

        return $money;
    }


    public function getTotalAttribute($total): Money
    {
        return Money::inDefaultCurrency($total);
    }


    /**
     * Get the product's base image.
     *
     * @return File
     */
    public function getBaseImageAttribute(): File
    {
        return $this->files
            ->where('pivot.zone', 'base_image')
            ->first()
            ?:
            new File();
    }


    /**
     * Get product's additional images.
     *
     * @return Collection
     */
    public function getAdditionalImagesAttribute(): Collection
    {
        return $this->files
            ->where('pivot.zone', 'additional_images')
            ->sortBy('pivot.id');
    }


    public function getMediaAttribute()
    {
        return $this->files
            ->whereIn('pivot.zone', ['base_image', 'additional_images'])
            ->sortBy('pivot.id');
    }


    /**
     * Get product's downloadable files.
     *
     * @return Collection
     */
    public function getDownloadsAttribute()
    {
        return $this->files
            ->where('pivot.zone', 'downloads')
            ->sortBy('pivot.id')
            ->flatten();
    }


    public function getDoesManageStockAttribute(): bool
    {
        return (bool)$this->manage_stock;
    }


    public function getQtyAttribute($qty)
    {
        return $qty;
    }


    public function getIsInStockAttribute(): bool
    {
        return (bool)$this->isInStock();
    }


    public function getIsOutOfStockAttribute(): bool
    {
        return $this->isOutOfStock();
    }


    public function getIsNewAttribute(): bool
    {
        return $this->isNew();
    }


    public function getAttributeSetsAttribute()
    {
        return $this->getAttribute('attributes')->groupBy('attributeSet');
    }


    public function getRatingPercentAttribute()
    {
        if ($this->relationLoaded('reviews')) {
            return ($this->reviews->avg->rating / 5) * 100;
        }
    }
}
