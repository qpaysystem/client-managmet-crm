<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('apartment_number', 50);
            $table->unsignedSmallInteger('floor')->nullable();
            $table->decimal('living_area', 10, 2)->nullable()->comment('Жилая площадь, м²');
            $table->unsignedTinyInteger('rooms_count')->nullable();
            $table->string('layout_photo_path')->nullable()->comment('Картинка планировки');
            $table->string('status', 30)->default('available')->comment('sold, in_pledge, available');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
