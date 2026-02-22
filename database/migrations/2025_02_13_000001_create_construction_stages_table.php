<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('construction_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255)->comment('Название этапа');
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete()->comment('Ответственный');
            $table->decimal('budget', 14, 2)->nullable()->comment('Бюджет');
            $table->string('contractor', 255)->nullable()->comment('Подрядчик');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('construction_stages');
    }
};
