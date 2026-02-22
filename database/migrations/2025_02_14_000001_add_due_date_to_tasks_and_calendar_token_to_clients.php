<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('budget')->comment('Срок выполнения');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('calendar_feed_token', 64)->nullable()->unique()->after('photo_path')->comment('Токен для подписки на календарь задач');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('calendar_feed_token');
        });
    }
};
