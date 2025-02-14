<?php

namespace App\Models;

use Carbon\Carbon;
use Laravel\Passport\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;


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

    public static function getTodaySales()
    {
        $today = Carbon::today();

        return User::whereDate('created_at', $today)->count() ?? 0;
    }

    public static function getYesterdaySales()
    {
        $yesterday = Carbon::yesterday();

        return User::whereDate('created_at', $yesterday)->count() ?? 0;
    }

    public static function getThisWeekSales()
    {

        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        return User::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count() ?? 0;
    }

    public static function getLastWeekSales()
    {

        $startOfLastWeek = Carbon::now()->startOfWeek()->subWeek();
        $endOfLastWeek = Carbon::now()->endOfWeek()->subWeek();

        return User::whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->count() ?? 0;
    }


    public static function getSalesForLast7Days()
    {
        $sevenDaysAgo = \Carbon\Carbon::now()->subDays(7);
        return User::where('created_at', '>=', $sevenDaysAgo)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day', 'asc')
            ->get();
    }

    public static function getSalesForLast4Weeks()
    {
        $fourWeeksAgo = \Carbon\Carbon::now()->subWeeks(4);

        return User::where('created_at', '>=', $fourWeeksAgo)->selectRaw('YEAR(created_at) as year, WEEK(created_at, 1) as week,  COUNT(*) as total')
        ->groupBy('year', 'week')
        ->orderBy('year', 'asc')
        ->orderBy('week', 'asc')
        ->get();
    }

    public static function getSalesByMonth()
    {
        return User::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total')
        ->groupBy('year', 'month')
        ->orderBy('year', 'asc')
        ->orderBy('month', 'asc')
        ->get();
    }

    public static function getSalesByYear()
    {
        return User::selectRaw('YEAR(created_at) as year, COUNT(*) as total')
        ->groupBy('year')
        ->orderBy('year', 'asc')
        ->get();
    }
}
