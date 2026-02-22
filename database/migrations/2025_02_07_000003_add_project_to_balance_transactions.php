<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->foreignId('project_expense_item_id')->nullable()->after('project_id')->constrained('project_expense_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('balance_transactions', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropForeign(['project_expense_item_id']);
        });
    }
};
