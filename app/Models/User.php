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


    public static function getSalesForLast7DaysX()
    {
        $sevenDaysAgo = \Carbon\Carbon::now()->subDays(7);
        return User::where('created_at', '>=', $sevenDaysAgo)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day', 'asc')
            ->get();
    }

    public static function getSalesForLast7Days()
    {
        $sevenDaysAgo = Carbon::now()->subDays(6)->startOfDay(); // Ensure 7 full days including today
        $today = Carbon::now()->startOfDay();

        // Generate all days in the range
        $dateRange = [];
        for ($date = $sevenDaysAgo->copy(); $date->lte($today); $date->addDay()) {
            $dateRange[$date->format('Y-m-d')] = [
                'day' => $date->format('Y-m-d'),
                'total' => 0, // Default zero
            ];
        }

        // Fetch actual user counts
        $userData = User::where('created_at', '>=', $sevenDaysAgo)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day', 'asc')
            ->get()
            ->keyBy('day');

        // Merge actual data into the full date range
        foreach ($dateRange as $key => $value) {
            if (isset($userData[$key])) {
                $dateRange[$key]['total'] = $userData[$key]->total;
            }
        }

        return array_values($dateRange);
    }

    public static function getSalesForLast4WeeksX()
    {
        $fourWeeksAgo = \Carbon\Carbon::now()->subWeeks(4);

        return User::where('created_at', '>=', $fourWeeksAgo)->selectRaw('YEAR(created_at) as year, WEEK(created_at, 1) as week,  COUNT(*) as total')
        ->groupBy('year', 'week')
        ->orderBy('year', 'asc')
        ->orderBy('week', 'asc')
        ->get();
    }

    public static function getSalesForLast4Weeks()
    {
        $fourWeeksAgo = Carbon::now()->subWeeks(4)->startOfWeek(); // Start of the week
        $thisWeek = Carbon::now()->startOfWeek();

        // Generate all weeks in the range
        $weekRange = [];
        for ($date = $fourWeeksAgo->copy(); $date->lte($thisWeek); $date->addWeek()) {
            $year = $date->format('Y');
            $week = $date->format('W'); // ISO week number
            $weekRange["$year-$week"] = [
                'year' => $year,
                'week' => $week,
                'total' => 0, // Default zero
            ];
        }

        // Fetch actual user counts
        $userData = User::where('created_at', '>=', $fourWeeksAgo)
            ->selectRaw('YEAR(created_at) as year, WEEK(created_at, 1) as week, COUNT(*) as total')
            ->groupBy('year', 'week')
            ->orderBy('year', 'asc')
            ->orderBy('week', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return "{$item->year}-{$item->week}";
            });

        // Merge actual data into the full week range
        foreach ($weekRange as $key => $value) {
            if (isset($userData[$key])) {
                $weekRange[$key]['total'] = $userData[$key]->total;
            }
        }

        return array_values($weekRange);
    }



    public static function getSalesByMonthX()
    {
        return User::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total')
        ->groupBy('year', 'month')
        ->orderBy('year', 'asc')
        ->orderBy('month', 'asc')
        ->get();
    }

    public static function getSalesByMonth()
    {
        $startMonth = Carbon::now()->subMonths(11)->startOfMonth(); // 12 months ago
        $endMonth = Carbon::now()->startOfMonth();

        // Generate all months in the range
        $monthRange = [];
        for ($date = $startMonth->copy(); $date->lte($endMonth); $date->addMonth()) {
            $year = $date->format('Y');
            $month = $date->format('n'); // Numeric month (1-12)
            $monthRange["$year-$month"] = [
                'year' => $year,
                'month' => $month,
                'total' => 0, // Default zero
            ];
        }

        // Fetch actual user counts
        $userData = User::selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total')
        ->groupBy('year', 'month')
        ->orderBy('year', 'asc')
        ->orderBy('month', 'asc')
        ->get()
            ->keyBy(function ($item) {
                return "{$item->year}-{$item->month}";
            });

        // Merge actual data into the full month range
        foreach ($monthRange as $key => $value) {
            if (isset($userData[$key])) {
                $monthRange[$key]['total'] = $userData[$key]->total;
            }
        }

        return array_values($monthRange);
    }


    public static function getSalesByYearX()
    {
        return User::selectRaw('YEAR(created_at) as year, COUNT(*) as total')
        ->groupBy('year')
        ->orderBy('year', 'asc')
        ->get();
    }

    public static function getSalesByYear()
    {
        $startYear = Carbon::now()->subYears(4)->year; // 5 years ago
        $currentYear = Carbon::now()->year;

        // Generate all years in the range
        $yearRange = [];
        for ($year = $startYear; $year <= $currentYear; $year++) {
            $yearRange[$year] = [
                'year' => $year,
                'total' => 0, // Default zero
            ];
        }

        // Fetch actual user counts
        $userData = User::selectRaw('YEAR(created_at) as year, COUNT(*) as total')
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get()
            ->keyBy('year');

        // Merge actual data into the full year range
        foreach ($yearRange as $year => $value) {
            if (isset($userData[$year])) {
                $yearRange[$year]['total'] = $userData[$year]->total;
            }
        }

        return array_values($yearRange);
    }

}
