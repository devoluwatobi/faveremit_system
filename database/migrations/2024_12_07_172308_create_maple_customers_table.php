<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMapleCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('maple_customers', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('maple_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('country')->default('NG');
            $table->string('status');
            $table->integer('tier');
            $table->string('dob')->nullable();
            $table->string('phone_country_code')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('address_street')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_state')->nullable();
            $table->string('address_country')->nullable();
            $table->string('address_postal_code')->nullable();
            $table->string('identification_number')->nullable();
            $table->string('identification_type')->nullable();
            $table->string('photo')->nullable();
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
        Schema::dropIfExists('maple_customers');
    }
}
