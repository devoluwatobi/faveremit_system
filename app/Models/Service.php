<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'icon',
        'description',
        'service_type',
        'transaction_icon',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all of the giftCardTransactions for the Service
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function giftCardTransactions()
    {
        return $this->hasMany(GiftCardTransaction::class);
    }
}
