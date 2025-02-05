<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannerPromotion extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'banner_url',
        'promotion_url',
        'status',
    ];
}
