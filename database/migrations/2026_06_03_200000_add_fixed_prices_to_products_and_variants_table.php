<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('fixed_prices')->nullable()->after('selling_price');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->json('fixed_prices')->nullable()->after('selling_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('fixed_prices');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('fixed_prices');
        });
    }
};
