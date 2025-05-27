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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger(column: 'idUser')->nullable(); // Colonne ID utilisateur
            $table->string('user_nom')->nullable(); // Copie du nom de l’utilisateur
            $table->string('user_prenom')->nullable(); // Copie du prénom de l’utilisateur
            $table->string('user_pseudo')->nullable(); // Ou email, selon l'usage
            $table->dateTime('user_doc')->nullable(); // Date de création de l'utilisateur
            $table->string('user_role')->nullable(); // Rôle de l'utilisateur

            $table->string('action');
            $table->string('table_concernee');
            $table->text('details')->nullable();
            $table->timestamps();

            $table->foreign('idUser')->references('id')->on('users')->onDelete('set null'); // Clé étrangère
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logs');
    }
};
