<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('construction_stages', function (Blueprint $table) {
            $table->string('status', 50)->default('not_started')->after('contractor');
            $table->date('planned_start_date')->nullable()->after('status');
            $table->date('planned_end_date')->nullable()->after('planned_start_date');
            $table->date('actual_start_date')->nullable()->after('planned_end_date');
            $table->date('actual_end_date')->nullable()->after('actual_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('construction_stages', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'planned_start_date',
                'planned_end_date',
                'actual_start_date',
                'actual_end_date',
            ]);
        });
    }
};
