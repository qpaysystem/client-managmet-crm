<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('amount')->constrained('projects')->nullOnDelete();
            $table->foreignId('project_expense_item_id')->nullable()->after('project_id')->constrained('project_expense_items')->nullOnDelete();
            $table->string('status')->default('unpaid')->after('priority');
            $table->timestamp('paid_at')->nullable()->after('status');

            $table->index(['project_id', 'status']);
            $table->index(['project_expense_item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_invoices', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'status']);
            $table->dropIndex(['project_expense_item_id']);

            $table->dropConstrainedForeignId('project_expense_item_id');
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn(['status', 'paid_at']);
        });
    }
};

