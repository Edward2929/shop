<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('bank_receipt_path')->nullable()->after('status');
            $table->timestamp('bank_receipt_uploaded_at')->nullable()->after('bank_receipt_path');
            $table->string('bank_receipt_status')->nullable()->after('bank_receipt_uploaded_at');
            $table->text('bank_receipt_admin_note')->nullable()->after('bank_receipt_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'bank_receipt_path',
                'bank_receipt_uploaded_at',
                'bank_receipt_status',
                'bank_receipt_admin_note',
            ]);
        });
    }
};
