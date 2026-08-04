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
        Schema::create('pret_outils', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outil_id')->constrained('outils')->onDelete('cascade');
            $table->foreignId('reparation_id')->constrained('reparations')->onDelete('cascade');
            $table->foreignId('mecanicien_id')->constrained('mecaniciens')->onDelete('cascade');
            $table->integer('quantite')->default(1);
            $table->enum('statut', ['prete', 'restitue'])->default('prete');
            $table->boolean('est_partage')->default(false);
            $table->unsignedBigInteger('parent_pret_id')->nullable();
            $table->timestamps();

            $table->foreign('parent_pret_id')->references('id')->on('pret_outils')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pret_outils');
    }
};
