<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuyCryptoTransaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'transaction_id',
        'user_id',
        'sender',
        'address',
        'crypto_id',
        'crypto_symbol',
        'usd_amount',
        'ngn_amount',
        'crypto_amount',
        'usd_rate',
        'rejected_reason',
        'approved_by',
        'rejected_by',
        'updated_by',
        'status',
        'fee_usd_amount',
        'fee_crypto_amount',
        'fee_symbol',
        'crypto_status',
        'proof',
        'timestamp'
    ];
}
