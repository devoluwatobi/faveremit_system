<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundTransaction extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'amount',
        'settlement',
        'charge',
        'reference',
        'profile_first_name',
        'profile_surname',
        'profile_phone_no',
        'profile_email',
        'profile_blacklisted',
        'account_name',
        'account_no',
        'bank_name',
        'acccount_reference',
        'transaction_status',
        'status',
        'payer_account_name',
        'payer_account_no',
        'payer_bank_name',
    ];
}
