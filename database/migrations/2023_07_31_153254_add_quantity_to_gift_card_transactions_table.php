<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuantityToGiftCardTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gift_card_transactions', function (Blueprint $table) {
            $table->integer("qty")->comment("submitted quantity")->default(1);
            $table->integer("approved_qty")->comment("approved quantity")->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gift_card_transactions', function (Blueprint $table) {
            $table->dropColumn("qty");
            $table->dropColumn("approved_qty");
        });
    }
}
