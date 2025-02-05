<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MapleCustomer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'maple_id',
        'first_name',
        'last_name',
        'email',
        'country',
        'status',
        'tier',
        'dob',
        'phone_country_code',
        'phone_number',
        'address_street',
        'address_city',
        'address_state',
        'address_country',
        'address_postal_code',
        'identification_number',
        'identification_type',
        'photo',
    ];
}
