<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_group_messages', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id', 32)->index();
            $table->unsignedBigInteger('message_id');
            $table->unsignedBigInteger('from_user_id')->nullable()->index();
            $table->string('from_username', 255)->nullable();
            $table->string('from_first_name', 255)->nullable();
            $table->text('text')->nullable();
            $table->timestamp('message_date')->nullable()->index();
            $table->timestamps();

            $table->unique(['chat_id', 'message_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_group_messages');
    }
};
