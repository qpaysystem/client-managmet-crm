<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('expense_article');
            $table->decimal('amount', 12, 2);
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('received_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('priority')->default('planned');
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->index(['priority', 'due_date']);
            $table->index(['responsible_user_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_invoices');
    }
};

