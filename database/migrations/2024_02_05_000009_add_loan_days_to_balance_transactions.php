<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->unsignedInteger('loan_days')->nullable()->after('operation_type')->comment('Количество дней займа');
        });
    }

    public function down(): void
    {
        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->dropColumn('loan_days');
        });
    }
};
