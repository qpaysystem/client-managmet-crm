<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->string('operation_type', 50)->nullable()->after('type')->comment('loan, loan_repayment, other_income');
            $table->date('loan_due_at')->nullable()->after('operation_type')->comment('Срок займа (обязательно при займе)');
        });
    }

    public function down(): void
    {
        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->dropColumn(['operation_type', 'loan_due_at']);
        });
    }
};
