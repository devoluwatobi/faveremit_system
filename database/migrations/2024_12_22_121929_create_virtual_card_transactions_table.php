<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVirtualCardTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('virtual_card_transactions', function (Blueprint $table) {
            $table->id();
            $table->double('usd_amount', 20, 2);
            $table->double('usd_fee', 20, 2)->nullable();
            $table->double('fx_rate', 20, 2)->nullable();
            $table->double('ngn_amount', 20, 2)->nullable();
            $table->double('ngn_fee', 20, 2)->nullable();
            $table->string('maple_card_id');
            $table->string('reference');
            $table->boolean('is_termination')->default(false);
            $table->string('type');
            $table->string('maple_status')->nullable();
            $table->integer('status')->default(0);
            $table->integer('user_id');
            $table->string('currency')->default('USD');
            $table->longText('maple_data')->nullable();
            $table->longText('payment_data')->nullable();
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
        Schema::dropIfExists('virtual_card_transactions');
    }
}
