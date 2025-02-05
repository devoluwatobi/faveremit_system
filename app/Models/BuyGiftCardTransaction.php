<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BuyGiftCardTransaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'reloadly_transaction_id',
        'amount',
        'user_currency_code',
        'reloadly_fee',
        'kdc_fee',
        'sms_fee',
        'reloadly_total',
        'recipient_email',
        'recipient_phone',
        'custom_identifier',
        'product_name',
        'status',
        'productId',
        'quantity',
        'unit_price',
        'product_currency_code',
        'brand_id',
        'brand_name',
        'user_id',
    ];
}
