<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_number',
        'account_name',
        'bank',
        'bank_name',
        'paystack_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
