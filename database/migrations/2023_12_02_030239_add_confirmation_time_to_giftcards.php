<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConfirmationTimeToGiftcards extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->integer('confirm_min')->default(1)->comment("minimum time it takes to approve the giftcard");
            $table->integer('confirm_max')->default(15)->comment("maximum time it takes to approve the giftcard");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gift_cards', function (Blueprint $table) {
            $table->dropColumn('confirm_min');
            $table->dropColumn('confirm_max');
        });
    }
}
