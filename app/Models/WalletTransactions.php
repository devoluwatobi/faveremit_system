<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransactions extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'bank_id',
        'approved_by',
        'rejected_by',
        'rejected_reason',
        'transaction_ref',
        'transaction_id',
        'bank',
        'bank_name',
        'account_name',
        'account_number',
        'status',
        'charge',
        'trx_status',
        'type',
        'server',
        'session_id',
        'data'
    ];
}
