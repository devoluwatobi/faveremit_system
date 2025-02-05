<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserFundBankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_name',
        'account_no',
        'bank_name',
        "auto_settlement",
        "reference",
        "status",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
