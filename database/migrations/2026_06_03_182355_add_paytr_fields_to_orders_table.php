<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPaytrFieldsToOrdersTable extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('paytr_merchant_oid', 64)->nullable()->after('status');
            $table->string('paytr_payment_type', 20)->nullable()->after('paytr_merchant_oid');
            $table->unsignedTinyInteger('paytr_installment_count')->nullable()->after('paytr_payment_type');
            $table->decimal('paytr_installment_amount', 10, 2)->nullable()->after('paytr_installment_count');
            $table->decimal('paytr_total_paid', 10, 2)->nullable()->after('paytr_installment_amount');
            $table->string('paytr_status', 20)->nullable()->after('paytr_total_paid');
            $table->text('paytr_raw_response')->nullable()->after('paytr_status');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'paytr_merchant_oid',
                'paytr_payment_type',
                'paytr_installment_count',
                'paytr_installment_amount',
                'paytr_total_paid',
                'paytr_status',
                'paytr_raw_response',
            ]);
        });
    }
}
