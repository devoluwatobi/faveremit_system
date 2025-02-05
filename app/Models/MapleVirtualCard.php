<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapleVirtualCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'maple_id',
        'name', // Cardholder's name
        'card_number', // Full card number
        'masked_pan', // Masked PAN
        'expiry', // Expiry date in MM/YY format
        'cvv', // CVV code
        'status',  // ['ACTIVE', 'INACTIVE', 'BLOCKED', 'EXPIRED'], // Card status
        'type', // ['VIRTUAL', 'PHYSICAL'], // Card type
        'issuer', // Issuer of the card
        'currency', // Currency (ISO code)
        'balance', // lowest denomination; Kobo or Cents", // Balance with precision
        'balance_updated_at', // Balance updated timestamp
        'auto_approve', // Auto approve flag
        'street', // Street address
        'city', // City
        'state', // State abbreviation
        'postal_code', // Postal code
        'country',
    ];
}
