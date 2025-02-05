<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGiftCardCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gift_card_categories', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("title");
            $table->decimal('amount', 10, 2);
            $table->integer('updated_by');
            $table->integer('status')->default(1)->comment("0 for not active, 1 for active");
            $table->integer("range_id");
            $table->integer("gift_card_country_id");
            $table->integer("gift_card_id");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gift_card_categories');
    }
}
