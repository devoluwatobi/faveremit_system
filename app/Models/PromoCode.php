<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PromoCode extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $fillable = [
        'code',
        'use_count',
        'starts',
        'ends',
        'gain',
        "used_by",
        "deleted_by",
        "deleted_at",
        "created_by",
    ];

    public function delete(): void
    {
        $this->deleted_at = now();
        $this->deleted_by = Auth::user()->id;
        $this->save();
    }
}
