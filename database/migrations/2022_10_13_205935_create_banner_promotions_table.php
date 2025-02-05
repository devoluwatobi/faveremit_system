<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBannerPromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banner_promotions', function (Blueprint $table) {
            $table->id();
            $table->string("title")->default("Promotion");
            $table->string("banner_url");
            $table->string("promotion_url")->nullable();
            $table->integer('status')->default(1)->comment("0 for not active, 1 for active");
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
        Schema::dropIfExists('banner_promotions');
    }
}
