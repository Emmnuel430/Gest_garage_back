<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Adds quantite_pretee column to pret_outils table.
     * This field tracks the quantity loaned for a specific pret entry,
     * separate from the total quantite field used for stock management.
     */
    public function up(): void
    {
        Schema::table('pret_outils', function (Blueprint $table) {
            // Quantity specifically loaned for this pret entry (default 1)
            $table->integer('quantite_pretee')->default(1)->after('quantite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pret_outils', function (Blueprint $table) {
            $table->dropColumn('quantite_pretee');
        });
    }
};
