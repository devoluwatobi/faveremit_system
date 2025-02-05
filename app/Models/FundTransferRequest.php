<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FundTransferRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'recepient_id',
        'amount',
        'charge',
        'type',
        'account_name',
        'account_no',
        'bank_name',
        'bank_code',
        'transaction_id',
        'transaction_status',
        'status',
        'server',
        'session_id',
        'data'
    ];

    public static function getTodaySales()
    {
        $today = Carbon::today();

        return FundTransferRequest::where("status", 1)->whereDate('created_at', $today)->selectRaw('SUM(amount) as total')->value('total') ?? 0;
    }

    public static function getYesterdaySales()
    {
        $yesterday = Carbon::yesterday();

        return FundTransferRequest::where("status", 1)->whereDate('created_at', $yesterday)->selectRaw('SUM(amount) as total')->value('total') ?? 0;
    }

    public static function getThisWeekSales()
    {

        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        return FundTransferRequest::where("status", 1)->whereBetween('created_at', [$startOfWeek, $endOfWeek])->selectRaw('SUM(amount) as total')->value('total') ?? 0;
    }

    public static function getLastWeekSales()
    {

        $startOfLastWeek = Carbon::now()->startOfWeek()->subWeek();
        $endOfLastWeek = Carbon::now()->endOfWeek()->subWeek();

        return FundTransferRequest::where("status", 1)->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])->selectRaw('SUM(amount) as total')->value('total') ?? 0;
    }

    public static function getSalesForLast7Days()
    {
        $sevenDaysAgo = \Carbon\Carbon::now()->subDays(7);
        return FundTransferRequest::where("status", 1)->where('created_at', '>=', $sevenDaysAgo)->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->get();
    }

    public static function getSalesForLast4Weeks()
    {
        $fourWeeksAgo = \Carbon\Carbon::now()->subWeeks(4);

        return FundTransferRequest::where("status", 1)->where('created_at', '>=', $fourWeeksAgo)->selectRaw('YEAR(created_at) as year, WEEK(created_at, 1) as week,  SUM(amount) as total')
            ->groupBy('year', 'week')
            ->orderBy('year', 'asc')
            ->orderBy('week', 'asc')
            ->get();
    }

    public static function getSalesByMonth()
    {
        return FundTransferRequest::where("status", 1)->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();
    }

    public static function getSalesByYear()
    {
        return FundTransferRequest::where("status", 1)->selectRaw('YEAR(created_at) as year, SUM(amount) as total')
            ->groupBy('year')
            ->orderBy('year', 'asc')
            ->get();
    }
}
