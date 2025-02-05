<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_id',
        'user_id',
        'user_agent',
        'device_id',
        'ip',
        "name",
        "os",
        "location",
        "isp",
        "data",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
