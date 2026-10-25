<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('outils', function (Blueprint $table) {
            $table->boolean('est_special')->default(false)->after('quantite');
            $table->unsignedInteger('prix_par_heure')->default(0)->after('est_special');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outils', function (Blueprint $table) {
            $table->dropColumn(['est_special', 'prix_par_heure']);
        });
    }
};
