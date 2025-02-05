<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualCardTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'usd_amount',
        'usd_fee',
        'fx_rate',
        'ngn_amount',
        'ngn_fee',
        'maple_card_id',
        'reference',
        'is_termination',
        'type',
        'maple_status',
        'status',
        'user_id',
        'currency',
        'maple_data',
        'payment_data',
    ];
}
