<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_project_investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('expense_item_name', 255)->comment('Статья расхода');
            $table->decimal('amount', 14, 2)->comment('Сумма');
            $table->text('comment')->nullable()->comment('Комментарий');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_project_investments');
    }
};
