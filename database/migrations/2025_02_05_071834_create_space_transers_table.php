<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('space_transfers', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id");
            $table->integer("recepient_id")->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('charge', 10, 2);
            $table->string('type');
            $table->string('note')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_no')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('transaction_status')->nullable();
            $table->string('transaction_id')->nullable();
            $table->integer('status');
            $table->string('session_id')->nullable();
            $table->string('server')->nullable();
            $table->json('data')->comment('data response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space_transfers');
    }
};
