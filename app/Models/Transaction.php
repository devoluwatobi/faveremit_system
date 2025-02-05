<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_id',
        'usd_amount',
        'usd_rate',
        'ngn_amount',
        'transaction_ref',
        'status',
        'icon',
        'utility_id',
        'utility_service',
        'title',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get all of the giftCardtransactions for the Transaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function giftCardtransactions()
    {
        return $this->hasMany(GiftCardTransaction::class);
    }
}
