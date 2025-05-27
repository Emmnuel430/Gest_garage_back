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
        // Migration: check_receptions
        Schema::create('check_receptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained('receptions')->onDelete('cascade');

            $table->boolean('essuie_glace');
            $table->boolean('vitres_avant');
            $table->boolean('vitres_arriere');
            $table->boolean('phares_avant');
            $table->boolean('phares_arriere');
            $table->boolean('pneus_secours');
            $table->boolean('cric');
            $table->boolean('peinture');
            $table->boolean('retroviseur');
            $table->boolean('kit_pharmacie');
            $table->boolean('triangle');
            $table->text('remarques')->nullable();
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
        Schema::dropIfExists('check_receptions');
    }
};
