<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBuyGiftCardTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buy_gift_card_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer("reloadly_transaction_id");
            $table->decimal("amount");
            $table->string("user_currency_code");
            $table->decimal("reloadly_fee");
            $table->decimal("kdc_fee");
            $table->decimal("sms_fee");
            $table->decimal("reloadly_total");
            $table->string("recipient_email");
            $table->string("recipient_phone");
            $table->string("custom_identifier");
            $table->string("product_name");
            $table->string("status");
            $table->integer("productId");
            $table->integer("quantity");
            $table->decimal("unit_price");
            $table->string("product_currency_code");
            $table->integer("brand_id");
            $table->integer("brand_name");
            $table->softDeletes();
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
        Schema::dropIfExists('buy_gift_card_transactions');
    }
}
