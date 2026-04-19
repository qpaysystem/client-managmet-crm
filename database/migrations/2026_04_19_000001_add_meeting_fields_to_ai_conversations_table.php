<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_conversations', function (Blueprint $table) {
            $table->string('kind', 20)->default('general')->after('task_id');
            $table->timestamp('meeting_at')->nullable()->after('kind');
            $table->timestamp('meeting_finalized_at')->nullable()->after('meeting_at');

            $table->index(['created_by_user_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::table('ai_conversations', function (Blueprint $table) {
            $table->dropIndex(['created_by_user_id', 'kind']);
            $table->dropColumn(['kind', 'meeting_at', 'meeting_finalized_at']);
        });
    }
};
