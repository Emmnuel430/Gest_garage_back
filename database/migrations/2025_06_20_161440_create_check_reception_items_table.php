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
        Schema::create('check_reception_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('check_reception_id')->constrained()->onDelete('cascade');
            $table->foreignId('check_item_id')->constrained()->onDelete('cascade');
            $table->string('valeur'); // ex: bon, mauvais, présent, absent
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
        Schema::dropIfExists('check_reception_items');
    }
};
