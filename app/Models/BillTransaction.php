<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'number',
        'approved_by',
        'type',
        'utility_id',
        'transaction_ref',
        'service_name',
        'service_icon',
        'package',
        'status',
        'trx_status'
    ];


    // CREATE CONSTANTS FOR STATUS
    const PENDING = 0;
    const REJECTED = 1;
    const APPROVED = 2;

    // create constants for type
    const AIRTIME = 'airtime';
    const DATA = 'data';
    const TV = 'tv';
    const ELECTRICITY = 'electricity';



    // create relationship with user
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
