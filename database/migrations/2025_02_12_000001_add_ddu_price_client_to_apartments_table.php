<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->string('ddu_contract_number', 100)->nullable()->after('owner_data')->comment('Номер ДДУ');
            $table->decimal('price', 14, 2)->nullable()->after('ddu_contract_number')->comment('Стоимость квартиры');
            $table->foreignId('client_id')->nullable()->after('price')->constrained('clients')->nullOnDelete()->comment('Ответственный за квартиру');
        });
    }

    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn(['ddu_contract_number', 'price']);
        });
    }
};
