<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores manual per-currency (fixed) prices for products and product
     * variants. When a product is flagged as `is_fixed_price`, the price for a
     * given currency is read from here as-is instead of being derived from the
     * default price via the exchange rate.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('priceable');
            $table->string('currency', 3);
            $table->decimal('price', 18, 4)->unsigned();
            $table->decimal('special_price', 18, 4)->unsigned()->nullable();
            $table->string('special_price_type')->nullable();
            $table->timestamps();

            $table->unique(['priceable_id', 'priceable_type', 'currency'], 'product_prices_priceable_currency_unique');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_prices');
    }
};
