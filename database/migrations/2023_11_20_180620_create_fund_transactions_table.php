<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFundTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fund_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id");
            $table->decimal('amount', 10, 2);
            $table->decimal('settlement', 10, 2);
            $table->decimal('charge', 10, 2);
            $table->string('reference');
            $table->string('profile_first_name');
            $table->string('profile_surname');
            $table->string('profile_phone_no');
            $table->string('profile_email');
            $table->string('profile_blacklisted');
            $table->string('account_name');
            $table->string('account_no');
            $table->string('bank_name');
            $table->string('acccount_reference');
            $table->string('transaction_status');
            $table->integer('status');
            $table->string('payer_account_name')->nullable();
            $table->string('payer_account_no')->nullable();
            $table->string('payer_bank_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fund_transactions');
    }
}
