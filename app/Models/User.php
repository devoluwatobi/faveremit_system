<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;


/**
 * @method latest()
 * @method selectRaw(string $string)
 * @method whereDate(string $string, \Carbon\Carbon $today)
 * @method whereBetween(string $string, array $array)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'phone',
        'password',
        'photo',
        'dob',
        'role',
        'country',
        'email_verified_at',
        'phone_verified_at',
        'api_token',
        'fcm',
        'referrer',
        'gender',
        'pin'
    ];

    // create constant for user role
    const ROLE_USER = '0';
    const ROLE_ADMIN = '1';
    const ROLE_SUPER_ADMIN = '2';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $attributes = [
        'role' => "0",
    ];


    public function Wallet()
    {
        return $this->hasOne(Wallet::class);
    }
    public function RewardWallet()
    {
        return $this->hasOne(RewardWallet::class);
    }
    public function giftcardTransactions()
    {
        return $this->hasMany(GiftCardTransaction::class);
    }
    public function banks()
    {
        return $this->hasMany(BankDetails::class);
    }

    /**
     * Get all of the walletTransaction for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function walletTransaction()
    {
        return $this->hasMany(WalletTransactions::class);
    }
}
