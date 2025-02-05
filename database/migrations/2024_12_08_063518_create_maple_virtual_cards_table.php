<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMapleVirtualCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('maple_virtual_cards', function (Blueprint $table) {
            $table->id();
            $table->string('maple_id');
            $table->string('customer_id');
            $table->integer('user_id');
            $table->string('name'); // Cardholder's name
            $table->string('card_number')->nullable(); // Full card number
            $table->string('masked_pan'); // Masked PAN
            $table->string('expiry')->nullable(); // Expiry date in MM/YY format
            $table->string('cvv')->nullable(); // CVV code
            $table->string('status')->comment("['ACTIVE', 'INACTIVE', 'BLOCKED', 'EXPIRED']"); // Card status
            $table->string('type')->comment("['VIRTUAL', 'PHYSICAL']"); // Card type
            $table->string('issuer'); // Issuer of the card
            $table->string('currency'); // Currency (ISO code)
            $table->decimal('balance', 20, 2)->default(0.00)->comment("lowest denomination; Kobo or Cents"); // Balance with precision
            $table->timestamp('balance_updated_at')->nullable(); // Balance updated timestamp
            $table->boolean('auto_approve')->default(false); // Auto approve flag
            $table->string('street')->nullable(); // Street address
            $table->string('city')->nullable(); // City
            $table->string('state')->nullable(); // State abbreviation
            $table->string('postal_code')->nullable(); // Postal code
            $table->string('country')->nullable();
            $table->timestamps(); // Created at and updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('maple_virtual_cards');
    }
}
