<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'title',
        'type',
        'content',
        'expires_at',
    ];
}
