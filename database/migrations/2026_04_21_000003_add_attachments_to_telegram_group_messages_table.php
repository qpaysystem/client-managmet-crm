<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_group_messages', function (Blueprint $table) {
            $table->string('message_type', 32)->nullable()->after('message_id');
            $table->string('caption', 2048)->nullable()->after('text');

            $table->string('file_id', 255)->nullable()->after('caption');
            $table->string('file_unique_id', 255)->nullable()->after('file_id');
            $table->string('file_name', 255)->nullable()->after('file_unique_id');
            $table->string('mime_type', 255)->nullable()->after('file_name');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');

            $table->index(['chat_id', 'message_type']);
        });
    }

    public function down(): void
    {
        Schema::table('telegram_group_messages', function (Blueprint $table) {
            $table->dropIndex(['chat_id', 'message_type']);
            $table->dropColumn([
                'message_type',
                'caption',
                'file_id',
                'file_unique_id',
                'file_name',
                'mime_type',
                'file_size',
            ]);
        });
    }
};

