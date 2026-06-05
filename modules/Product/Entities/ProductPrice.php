<?php

namespace Modules\Product\Entities;

use Modules\Support\Money;
use Modules\Support\Eloquent\Model;

class ProductPrice extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'currency',
        'price',
        'special_price',
        'special_price_type',
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;


    /**
     * Get the parent priceable model (product or product variant).
     */
    public function priceable()
    {
        return $this->morphTo();
    }


    /**
     * Get the price as a Money object in this row's currency.
     */
    public function price(): Money
    {
        return new Money($this->attributes['price'], $this->currency);
    }


    /**
     * Get the discounted special price as a Money object in this row's currency.
     *
     * Mirrors the default-price special-price calculation but works entirely
     * within the fixed currency (no exchange rate involved).
     */
    public function specialPrice(): Money
    {
        $specialPrice = $this->attributes['special_price'];

        if ($this->special_price_type === 'percent') {
            $discountedPrice = ($specialPrice / 100) * $this->attributes['price'];

            $specialPrice = $this->attributes['price'] - $discountedPrice;
        }

        if ($specialPrice < 0) {
            $specialPrice = 0;
        }

        return new Money($specialPrice, $this->currency);
    }


    /**
     * Determine whether this row defines a special price amount.
     */
    public function hasSpecialPriceAmount(): bool
    {
        return !is_null($this->attributes['special_price']);
    }
}
