<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGiftCardTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gift_card_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('gift_card_country_id');
            $table->integer('service_id');
            $table->integer('gift_card_id');
            $table->integer('card_range_id');
            $table->integer('transaction_id');
            $table->integer('rate');
            $table->string('card_value');
            $table->string('receipt_availability');
            $table->string('proof');
            $table->text('rejected_reason')->nullable();
            $table->string('note')->nullable();
            $table->integer('approved_by')->nullable();
            $table->integer('rejected_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('status')->default(0)->comment('0 for pending, 1 for Approved, 2 for Rejeceted, 3 for cancelled');
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
        Schema::dropIfExists('gift_card_transactions');
    }
}
