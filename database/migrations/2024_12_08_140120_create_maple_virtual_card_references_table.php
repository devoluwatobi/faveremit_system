<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMapleVirtualCardReferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('maple_virtual_card_references', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id");
            $table->string("maple_id");
            $table->string("reference");
            $table->longText("data");
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
        Schema::dropIfExists('maple_virtual_card_references');
    }
}
