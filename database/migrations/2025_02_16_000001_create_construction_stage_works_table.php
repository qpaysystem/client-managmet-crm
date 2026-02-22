<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('construction_stage_works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('construction_stage_id')->constrained()->cascadeOnDelete();
            $table->date('work_start_date')->nullable()->comment('Дата начала работ');
            $table->string('materials_name', 500)->nullable()->comment('Наименование материалов');
            $table->decimal('materials_cost', 14, 2)->nullable()->comment('Стоимость материалов');
            $table->string('works_name', 500)->nullable()->comment('Наименование работ');
            $table->decimal('works_cost', 14, 2)->nullable()->comment('Стоимость работ');
            $table->string('contractor', 255)->nullable()->comment('Подрядчик');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('construction_stage_works');
    }
};
