<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('construction_stage_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('construction_stage_id')->constrained()->cascadeOnDelete();
            $table->string('path', 500)->comment('Путь к файлу в storage');
            $table->string('caption', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('construction_stage_photos');
    }
};
