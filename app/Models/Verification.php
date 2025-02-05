<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Verification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        "value",
        'name',
        'verification_status',
        "reference",
        "status",
        "dob",
        "data",
        "updates",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
