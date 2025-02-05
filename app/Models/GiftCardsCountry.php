<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GiftCardsCountry extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'gift_card_id',
        'country_id',
        'status',

    ];

    /**
     * Get the giftCard that owns the GiftCardsCountry
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function giftCard()
    {
        return $this->belongsTo(GiftCard::class);
    }
}
