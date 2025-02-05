<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image',
        'service_id',
        'brand_logo',
        'status',
        'updated_by',
        'confirm_min',
        'confirm_max'
    ];

    /**
     * Get the service that owns the GiftCard
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get all of the ranges for the GiftCard
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ranges()
    {
        return $this->hasMany(CardRange::class);
    }

    public function cardCountries()
    {
        return $this->hasMany(GiftCardsCountry::class);
    }



    /**
     * Get all of the transactions for the GiftCard
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactions()
    {
        return $this->hasMany(GiftCardTransaction::class);
    }
}
