<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GiftCardTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gift_card_country_id',
        'service_id',
        'gift_card_id',
        'card_range_id',
        'gift_card_category_id',
        'rate',
        'card_value',
        'proof',
        'proofs',
        'second_proof',
        'third_proof',
        'rejected_reason',
        'response_image',
        'note',
        'approved_by',
        'rejected_by',
        'status',
        "approved_qty",
        "qty",
        "promo_code",
    ];

    /**
     * Get the user that owns the GiftCardTransaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the country that owns the GiftCardTransaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country()
    {
        return $this->belongsTo(country::class);
    }

    /**
     * Get the gitcard that owns the GiftCardTransaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function gitcard()
    {
        return $this->belongsTo(GiftCard::class);
    }

    /**
     * Get the service that owns the GiftCardTransaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
