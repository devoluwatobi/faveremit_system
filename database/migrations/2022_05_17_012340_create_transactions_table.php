<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('service_id');
            $table->integer('utility_id')->nullable();
            $table->decimal('usd_amount', 10, 2)->nullable();
            $table->decimal('usd_rate', 10, 2)->nullable();
            $table->decimal('ngn_amount', 10, 2)->nullable();
            $table->string('transaction_ref');
            $table->string('icon')->nullable();
            $table->string('title')->nullable();
            $table->string('utility_service')->nullable();
            $table->integer('status')->default(0)->comment("0 for pending, 1 for Approved, 2 for Rejected, 3 for cancelled");
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
        Schema::dropIfExists('transactions');
    }
}
