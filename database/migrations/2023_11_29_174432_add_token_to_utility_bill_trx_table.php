<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTokenToUtilityBillTrxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bill_transactions', function (Blueprint $table) {
            $table->string('token')->nullable()->comment("token for electricity and any one that needs");
            $table->string('trx_status')->default("Approved")->comment("transaction status from provider");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bill_transactions', function (Blueprint $table) {
            $table->dropColumn('token');
            $table->dropColumn('trx_status');
        });
    }
}
