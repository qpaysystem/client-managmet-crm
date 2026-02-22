<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('show_on_site')->default(false)->after('description')->comment('Показывать на главной странице');
            $table->text('site_description')->nullable()->after('show_on_site')->comment('Описание для сайта');
            $table->string('map_embed_url', 1000)->nullable()->after('site_description')->comment('URL iframe карты Яндекса');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['show_on_site', 'site_description', 'map_embed_url']);
        });
    }
};
