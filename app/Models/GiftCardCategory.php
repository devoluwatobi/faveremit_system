<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GiftCardCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'amount',
        'status',
        'updated_by',
        'range_id',
        'gift_card_country_id',
        'gift_card_id',
    ];
}
