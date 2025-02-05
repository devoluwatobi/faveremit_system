<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusAndTimestampToUserCryptoWalletTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_crypto_wallet_transactions', function (Blueprint $table) {
            $table->string('crypto_status');
            $table->integer('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_crypto_wallet_transactions', function (Blueprint $table) {
            $table->dropColumn('crypto_status');
            $table->dropColumn('timestamp');
        });
    }
}
