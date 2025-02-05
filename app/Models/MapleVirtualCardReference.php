<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapleVirtualCardReference extends Model
{
    use HasFactory;

    protected $fillable = [
        'maple_id',
        'user_id',
        'reference',
        'data',
    ];
}
