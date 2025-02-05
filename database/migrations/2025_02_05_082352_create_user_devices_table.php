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
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->string('token_id');
            $table->string('user_id');
            $table->string('user_agent');
            $table->string('device_id')->nullable();
            $table->string('ip');
            $table->string('name')->nullable();
            $table->string('os')->nullable();
            $table->string('location')->nullable();
            $table->string('isp')->nullable();
            $table->longText('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
