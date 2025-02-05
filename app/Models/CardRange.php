<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CardRange extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'gift_card_id',
        'gift_card_country_id',
        'min',
        'max',
        // 'rate',
        // 'ecode_rate',
        // 'physical_rate',
        'updated_by',
        'status'
    ];

    public function country()
    {
        return $this->belongsTo(country::class);
    }
    public function giftcard()
    {
        return $this->belongsTo(GiftCard::class);
    }
}
