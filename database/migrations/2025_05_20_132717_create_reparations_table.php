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
        // Migration: reparations
        Schema::create('reparations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained('receptions')->onDelete('cascade');
            $table->foreignId('chef_atelier_id')->nullable()->constrained('users')->onDelete('set null');

            $table->text('description');
            $table->enum('statut', ['en_cours', 'termine'])->default('en_cours');
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
        Schema::dropIfExists('reparations');
    }
};
