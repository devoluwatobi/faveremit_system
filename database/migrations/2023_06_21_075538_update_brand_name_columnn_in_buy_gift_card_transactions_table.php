<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBrandNameColumnnInBuyGiftCardTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('buy_gift_card_transactions', function (Blueprint $table) {
            $table->string("brand_name")->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('buy_gift_card_transactions', function (Blueprint $table) {
            $table->integer("brand_name")->change();
        });
    }
}
