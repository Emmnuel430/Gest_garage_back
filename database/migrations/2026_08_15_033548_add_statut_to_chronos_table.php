<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chronos', function (Blueprint $table) {
            // 'en_cours' par défaut quand on démarre. Peut passer à 'en_pause' ou 'termine'
            $table->string('statut')->default('en_cours')->after('reception_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chronos', function (Blueprint $table) {
            $table->dropColumn('statut');
        });
    }
};
