<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Migration: receptions
        Schema::create('receptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicule_id')->constrained('vehicules')->onDelete('cascade');
            $table->foreignId('gardien_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('secretaire_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('chef_atelier_id')->nullable()->constrained('users')->onDelete('set null');

            $table->dateTime('date_arrivee');
            $table->text('motif_visite');
            $table->string('fiche_reception_vehicule')->nullable();
            $table->enum('statut', ['attente', 'validee', 'termine'])->default('attente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('receptions');
    }
};
