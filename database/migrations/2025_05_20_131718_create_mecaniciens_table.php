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
        // Migration: mecaniciens
        Schema::create('mecaniciens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('garage_id')->nullable()->constrained('garages')->onDelete('cascade');

            $table->string('nom');
            $table->string('prenom');
            $table->enum('type', ['interne', 'externe']);
            $table->text('vehicules_maitrises')->nullable();
            $table->integer('experience')->nullable();
            $table->string('contact');
            $table->string('contact_urgence');
            $table->string('fiche_enrolement')->nullable();
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
        Schema::dropIfExists('mecaniciens');
    }
};
