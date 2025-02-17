<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GiftCardTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gift_card_country_id',
        'service_id',
        'gift_card_id',
        'card_range_id',
        'gift_card_category_id',
        'rate',
        'card_value',
        'proof',
        'proofs',
        'second_proof',
        'third_proof',
        'rejected_reason',
        'response_image',
        'note',
        'approved_by',
        'rejected_by',
        'status',
        "approved_qty",
        "qty",
        "promo_code",
    ];

    /**
     * Get the user that owns the GiftCardTransaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the country that owns the GiftCardTransaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country()
    {
        return $this->belongsTo(country::class);
    }

    /**
     * Get the gitcard that owns the GiftCardTransaction
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function gitcard()
    {
        return $this->belongsTo(GiftCard::class);
    }

    public static function getSalesForLast7DaysX()
    {
        $sevenDaysAgo = \Carbon\Carbon::now()->subDays(7);
        return GiftCardTransaction::where("status", 1)->where('created_at', '>=', $sevenDaysAgo)->selectRaw('DATE(created_at) as day, SUM(rate * card_value) as total')
        ->groupBy('day')
        ->get();
    }

    public static function getSalesForLast7Days()
    {
        $sevenDaysAgo = Carbon::now()->subDays(6); // Subtract 6 because we include today
        $today = Carbon::now();

        // Generate an array of dates for the last 7 days
        $dateRange = [];
        for ($date = $sevenDaysAgo->copy(); $date->lte($today); $date->addDay()) {
            $dateRange[] = $date->format('Y-m-d');
        }

        // Fetch actual sales data
        $salesData = GiftCardTransaction::where("status", 1)
        ->where('created_at', '>=', $sevenDaysAgo)
            ->selectRaw('DATE(created_at) as day, SUM(rate * card_value) as total')
            ->groupBy('day')
            ->pluck('total', 'day') // Get key-value pairs of day => total
            ->toArray();

        // Fill missing days with zero sales
        $formattedResults = [];
        foreach ($dateRange as $date) {
            $formattedResults[] = [
                'day' => $date,
                'total' => $salesData[$date] ?? 0, // Use actual data or 0 if missing
            ];
        }

        return $formattedResults;
    }

    public static function getSalesForLast4WeeksX()
    {
        $fourWeeksAgo = \Carbon\Carbon::now()->subWeeks(4);

        return GiftCardTransaction::where("status", 1)
        ->where('created_at', '>=', $fourWeeksAgo)
            ->selectRaw('YEAR(created_at) as year, WEEK(created_at, 1) as week, SUM(rate * card_value * qty) as total')
            ->groupBy('year', 'week')
            ->orderBy('year', 'asc')
            ->orderBy('week', 'asc')
            ->get();
    }

    public static function getSalesForLast4Weeks()
    {
        $fourWeeksAgo = Carbon::now()->subWeeks(4)->startOfWeek(); // Start from the beginning of that week
        $thisWeek = Carbon::now()->startOfWeek(); // Current week's start

        // Generate all week numbers in the range
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

        // Fetch actual sales data
        $salesData = GiftCardTransaction::where("status", 1)
        ->where('created_at', '>=', $fourWeeksAgo)
            ->selectRaw('YEAR(created_at) as year, WEEK(created_at, 1) as week, SUM(rate * card_value * qty) as total')
            ->groupBy('year', 'week')
            ->orderBy('year', 'asc')
            ->orderBy('week', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return "{$item->year}-{$item->week}";
            });

        // Merge actual sales into the full week range
        foreach ($weekRange as $key => $value) {
            if (isset($salesData[$key])) {
                $weekRange[$key]['total'] = $salesData[$key]->total;
            }
        }

        return array_values($weekRange);
    }

    public static function getSalesByMonthX()
    {
        return GiftCardTransaction::where("status", 1)->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(rate * card_value * qty) as total')
        ->groupBy('year', 'month')
        ->orderBy('year', 'asc')
        ->orderBy('month', 'asc')
        ->get();
    }

    public static function getSalesByMonth()
    {
        $startMonth = Carbon::now()->subMonths(11)->startOfMonth(); // 12 months ago
        $endMonth = Carbon::now()->startOfMonth(); // Current month

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

        // Fetch actual sales data
        $salesData = GiftCardTransaction::where("status", 1)
        ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(rate * card_value * qty) as total')
        ->groupBy('year', 'month')
        ->orderBy('year', 'asc')
        ->orderBy('month', 'asc')
        ->get()
            ->keyBy(function ($item) {
                return "{$item->year}-{$item->month}";
            });

        // Merge actual sales into the full month range
        foreach ($monthRange as $key => $value) {
            if (isset($salesData[$key])) {
                $monthRange[$key]['total'] = $salesData[$key]->total;
            }
        }

        return array_values($monthRange);
    }

    public static function getSalesByYearX()
    {
        return GiftCardTransaction::where("status", 1)->selectRaw('YEAR(created_at) as year, SUM(rate * card_value * qty) as total')
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

        // Fetch actual sales data
        $salesData = GiftCardTransaction::where("status", 1)
            ->selectRaw('YEAR(created_at) as year, SUM(rate * card_value * qty) as total')
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get()
            ->keyBy('year');

        // Merge actual sales into the full year range
        foreach ($yearRange as $year => $value) {
            if (isset($salesData[$year])) {
                $yearRange[$year]['total'] = $salesData[$year]->total;
            }
        }

        return array_values($yearRange);
    }

}
