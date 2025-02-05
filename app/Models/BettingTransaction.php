<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BettingTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'reference',
        'charge',
        'product',
        'customer_id',
        'first_name',
        'surname',
        'username',
        'date',
        'bet_status',
        'status',
    ];
}
