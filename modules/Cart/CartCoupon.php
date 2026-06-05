<?php

namespace Modules\Cart;

use JsonSerializable;
use Modules\Support\Money;

class CartCoupon implements JsonSerializable
{
    private $cart;
    private $coupon;
    private $couponCondition;


    public function __construct($cart, $coupon, $couponCondition)
    {
        $this->cart = $cart;
        $this->coupon = $coupon;
        $this->couponCondition = $couponCondition;
    }


    public function entity()
    {
        return $this->coupon;
    }


    public function id()
    {
        return $this->coupon->id;
    }


    public function isFreeShipping()
    {
        return $this->coupon->free_shipping;
    }


    public function usageLimitReached($customerEmail)
    {
        return $this->fresh()->coupon->usageLimitReached($customerEmail);
    }


    public function fresh(): static
    {
        $this->coupon = $this->coupon->refresh();

        return $this;
    }


    public function didNotSpendTheRequiredAmount()
    {
        return $this->fresh()->coupon->didNotSpendTheRequiredAmount();
    }


    public function spentMoreThanMaximumAmount()
    {
        return $this->fresh()->coupon->spentMoreThanMaximumAmount();
    }


    public function usedOnce()
    {
        $this->coupon->increment('used');
    }


    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }


    public function jsonSerialize()
    {
        return $this->toArray();
    }


    public function toArray(): array
    {
        return [
            'code' => $this->code(),
            'value' => $this->value(),
            'free_shipping' => $this->coupon->free_shipping,
        ];
    }


    public function code()
    {
        return $this->coupon->code;
    }


    public function value()
    {
        $value = Money::inDefaultCurrency($this->calculate());

        // Percentage coupons scale with the (fixed-price aware) cart total, so
        // resolve them in the current currency. Fixed-amount coupons are an
        // absolute default-currency amount and stay rate-based.
        if ($this->coupon->is_percent) {
            return $value->withCurrentAmount($this->calculateInCurrentCurrency());
        }

        return $value;
    }


    public function __get($attribute)
    {
        return $this->coupon->{$attribute};
    }


    private function calculate()
    {
        return $this->couponCondition
            ->getCalculatedValue($this->couponApplicableProductsTotalPrice());
    }


    private function calculateInCurrentCurrency()
    {
        return $this->couponCondition
            ->getCalculatedValue($this->couponApplicableProductsCurrentTotalPrice());
    }


    private function couponApplicableProductsTotalPrice()
    {
        return $this->couponApplicableProducts()
            ->sum(function ($cartItem) {
                return $cartItem->totalPrice()->amount();
            });
    }


    private function couponApplicableProductsCurrentTotalPrice()
    {
        return $this->couponApplicableProducts()
            ->sum(function ($cartItem) {
                return $cartItem->totalPrice()->valueInCurrentCurrency();
            });
    }


    private function couponApplicableProducts()
    {
        return $this->cart->items()->filter(function ($cartItem) {
            return $this->inApplicableProducts($cartItem);
        })->reject(function ($cartItem) {
            return $this->inExcludedProducts($cartItem);
        })->filter(function ($cartItem) {
            return $this->inApplicableCategories($cartItem);
        })->reject(function ($cartItem) {
            return $this->inExcludedCategories($cartItem);
        });
    }


    private function inApplicableProducts($cartItem)
    {
        if ($this->coupon->products->isEmpty()) {
            return true;
        }

        return $this->coupon
            ->products
            ->contains($cartItem->product);
    }


    private function inExcludedProducts($cartItem)
    {
        if ($this->coupon->excludeProducts->isEmpty()) {
            return false;
        }

        return $this->coupon
            ->excludeProducts
            ->contains($cartItem->product);
    }


    private function inApplicableCategories($cartItem)
    {
        if ($this->coupon->categories->isEmpty()) {
            return true;
        }

        return $this->coupon
            ->categories
            ->intersect($cartItem->product->categories)
            ->isNotEmpty();
    }


    private function inExcludedCategories($cartItem)
    {
        if ($this->coupon->excludeCategories->isEmpty()) {
            return false;
        }

        return $this->coupon
            ->excludeCategories
            ->intersect($cartItem->product->categories)
            ->isNotEmpty();
    }
}
