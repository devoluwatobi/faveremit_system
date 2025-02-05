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
        Schema::create('two_factor_auth_data', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id");
            $table->string('secret');
            $table->json('backup')->nullable()->comment("backup codes for 2FA");
            $table->integer("use_2fa")->default('0');
            $table->longText('settings')->nullable()->comment("settings option");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('two_factor_auth_data');
    }
};
