<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCardRangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('card_ranges', function (Blueprint $table) {
            $table->id();
            $table->integer('gift_card_id');
            $table->integer('gift_card_country_id');
            $table->integer('min');
            $table->integer('max');
            $table->integer('status')->default(1)->comment("0 for not active, 1 for active");
            // $table->integer('rate')->nullable();
            $table->integer('updated_by')->nullable();
            // $table->integer('ecode_rate')->nullable();
            // $table->integer('physical_rate')->nullable();
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
        Schema::dropIfExists('card_ranges');
    }
}
