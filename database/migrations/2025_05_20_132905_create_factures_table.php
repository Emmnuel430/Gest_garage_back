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
        // Migration: factures
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained('receptions')->onDelete('cascade');
            $table->foreignId('caissier_id')->nullable()->constrained('users')->onDelete('set null');

            $table->decimal('montant', 10, 2);
            $table->dateTime('date_generation');
            $table->enum('statut', ['en_attente', 'payee'])->default('en_attente');
            $table->string('recu')->nullable();
            $table->dateTime('date_paiement')->nullable();
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
        Schema::dropIfExists('factures');
    }
};
