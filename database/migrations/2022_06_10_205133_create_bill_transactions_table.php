<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBillTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bill_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->decimal('amount');
            $table->string('number')->nullable();
            $table->string('type')->nullable();
            $table->string('package')->nullable();
            $table->string('service_icon')->nullable();
            $table->string('service_name')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->integer('status')->default(2)->comment('0 for pending, 1 for failed, 2 for completed');
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
        Schema::dropIfExists('bill_transactions');
    }
}
