<?php

namespace Modules\Support;

class ProductMoney extends Money
{
    protected array $fixedPrices;

    public function __construct($amount, $currency, array $fixedPrices = [])
    {
        parent::__construct($amount, $currency);
        $this->fixedPrices = $fixedPrices;
    }

    public static function inDefaultCurrencyWithFixed($amount, array $fixedPrices): self
    {
        return new self($amount, setting('default_currency'), $fixedPrices);
    }

    public function convertToCurrentCurrency($currencyRate = null): Money
    {
        $current = currency();

        if (isset($this->fixedPrices[$current])
            && $this->fixedPrices[$current] !== null
            && $this->fixedPrices[$current] !== ''
        ) {
            return new Money((float) $this->fixedPrices[$current], $current);
        }

        return parent::convertToCurrentCurrency($currencyRate);
    }

    protected function newInstance($amount): static
    {
        return new self($amount, $this->currency(), $this->fixedPrices);
    }
}
