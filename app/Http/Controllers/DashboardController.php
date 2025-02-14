<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Country;
use App\Models\GiftCard;
use App\Models\Currencies;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use App\Models\GeneralCountry;
use App\Models\BillTransaction;
use App\Models\FundTransaction;
use App\Models\GiftCardsCountry;
use Illuminate\Http\JsonResponse;
use App\Models\BettingTransaction;
use App\Models\WalletTransactions;
use Illuminate\Support\Facades\DB;
use App\Models\FundTransferRequest;
use App\Models\GiftCardTransaction;
use App\Http\Resources\UserResource;
use App\Models\BuyGiftCardTransaction;
use App\Models\VirtualCardTransaction;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 *
 */
class DashboardController extends Controller
{
    /**
     * @var User
     */
    private User $userModel;
    /**
     * @var Carbon
     */
    private Carbon $today;
    /**
     * @var Carbon
     */
    private Carbon $monthStartDate;
    /**
     * @var Carbon
     */
    private Carbon $monthEndDate;
    /**
     * @var Carbon
     */
    private Carbon $weekStartDate;
    /**
     * @var Carbon
     */
    private Carbon $weekEndDate;
    /**
     * @var Carbon
     */
    private Carbon $lastWeekStart;
    /**
     * @var Carbon
     */
    private Carbon $lastWeekEnd;
    /**
     * @var Carbon
     */
    private Carbon $previousWeekStart;
    /**
     * @var Carbon
     */
    private Carbon $previousWeekEnd;

    /**
     * @param User $userModel
     */
    public function __construct(User $userModel)
    {
        $this->userModel = $userModel;
        $this->today = Carbon::now();
        $this->monthStartDate = Carbon::now()->startOfMonth();
        $this->monthEndDate = Carbon::now()->endOfMonth();
        $this->weekStartDate = Carbon::now()->startOfWeek();
        $this->weekEndDate = Carbon::now()->endOfWeek();
        $this->lastWeekStart = Carbon::now()->subWeek()->startOfWeek();
        $this->lastWeekEnd = Carbon::now()->subWeek()->endOfWeek();
        $this->previousWeekStart = Carbon::now()->subWeek(2)->startOfWeek();
        $this->previousWeekEnd = Carbon::now()->subWeek(2)->endOfWeek();
    }

    /**
     * @return AnonymousResourceCollection
     */
    public function indexMethod(): AnonymousResourceCollection
    {
        $users = $this->userModel->latest()->paginate(20);
        return UserResource::collection($users)->additional([
            'message' => "All users  fetched successfully",
            'success' => true
        ], Response::HTTP_OK);
    }

    /**
     * @return JsonResponse
     */
    public function usersCountMethod(): JsonResponse
    {
        $totalUsersCount = $this->userModel->all()->count();
        $todayUsers = $this->userModel->whereDate('created_at', $this->today)->count();
        $thisMonthUsers = $this->userModel->whereBetween('created_at', [$this->monthStartDate, $this->monthEndDate])->count();
        $thisWeekUsers = $this->userModel->whereBetween('created_at', [$this->weekStartDate, $this->weekEndDate])->count();
        return response()->json([
            'total_users_count' => $totalUsersCount,
            'today_users_count' => $todayUsers,
            'this_month_users_count' => $thisMonthUsers,
            'week_users_count' => $thisWeekUsers
        ]);
    }

    /**
     * gazette github graph
     * @param Request $request
     * @return JsonResponse
     */
    public function getUsersGraphMethod(Request $request): JsonResponse
    {
        if ($request->input('type') == 'weekly') {
            $usersByDay = $this->userModel->selectRaw('DAYNAME(created_at) as day, COUNT(*) as total')
                ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->groupBy('day')
                ->orderByRaw('FIELD(DAYNAME(created_at), "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday")')
                ->get()
                ->mapWithKeys(fn($item) => [$item->day => $item->total]);
            return response()->json([
                'message' => "All data fetched successfully",
                'data' => $usersByDay,
                'success' => true
            ], Response::HTTP_OK);
        } elseif ($request->input('type') == 'monthly') {
            $usersByWeeks = $this->userModel->selectRaw("
                        CASE
                            WHEN DAY(created_at) BETWEEN 1 AND 7 THEN 'Week 1'
                            WHEN DAY(created_at) BETWEEN 8 AND 14 THEN 'Week 2'
                            WHEN DAY(created_at) BETWEEN 15 AND 21 THEN 'Week 3'
                            WHEN DAY(created_at) BETWEEN 22 AND 31 THEN 'Week 4'
                        END as week_group, COUNT(*) as total
                    ")
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->groupBy('week_group')
                ->orderBy('week_group')
                ->get()
                ->mapWithKeys(fn($item) => [$item->week_group => $item->total]);
            return response()->json([
                'message' => "All data fetched successfully",
                'data' => $usersByWeeks,
                'success' => true
            ], Response::HTTP_OK);
        } else {
            $usersByMonth = $this->userModel->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                ->whereYear('created_at', Carbon::now()->year)
                ->groupBy('month')
                ->orderBy('month')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [Carbon::createFromFormat('!m', $item->month)->format('F') => $item->total];
                });

            $months = collect(range(1, 12))->mapWithKeys(function ($month) use ($usersByMonth) {
                $monthName = Carbon::createFromFormat('!m', $month)->format('F');
                return [$monthName => $usersByMonth[$monthName] ?? 0];
            });
            return response()->json([
                'message' => "All data fetched successfully",
                'data' => $months,
                'success' => true
            ], Response::HTTP_OK);
        }
    }


    public function index()
    {
        // total revenue
        $totalTransaction = BillTransaction::where('status', 1)->sum('amount')
        + BettingTransaction::where('status', 1)->sum('amount')
        + BuyGiftCardTransaction::sum('amount')
        + FundTransferRequest::sum('amount')
        + FundTransaction::where('status', 1)->sum('amount')
        + VirtualCardTransaction::where('status', 1)->sum('ngn_amount')
        + GiftCardTransaction::where('status', 1)->sum(DB::raw('qty * card_value * rate'));


        // Get revenue for last week
        $totalTransactionLastWeek = BillTransaction::where('status', 1)->whereBetween('created_at', [$this->lastWeekStart, $this->lastWeekEnd])->sum('amount')
        + BettingTransaction::where('status', 1)->whereBetween('created_at', [$this->lastWeekStart, $this->lastWeekEnd])->sum('amount')
        + BuyGiftCardTransaction::whereBetween('created_at', [$this->lastWeekStart, $this->lastWeekEnd])->sum('amount')
        + FundTransferRequest::whereBetween('created_at', [$this->lastWeekStart, $this->lastWeekEnd])->sum('amount')
        + VirtualCardTransaction::whereBetween('created_at', [$this->lastWeekStart, $this->lastWeekEnd])->sum('ngn_amount')
        + FundTransaction::where('status', 1)->whereBetween('created_at', [$this->lastWeekStart, $this->lastWeekEnd])->sum('amount')
        + GiftCardTransaction::where('status', 1)->whereBetween('created_at', [$this->lastWeekStart, $this->lastWeekEnd])->sum(DB::raw('qty * card_value * rate'));

        // Get revenue for previous week
        $totalTransactionPreviousWeek =
            BillTransaction::where('status', 1)->whereBetween('created_at', [$this->previousWeekStart, $this->previousWeekEnd])->sum('amount')
            + BettingTransaction::where('status', 1)->whereBetween('created_at', [$this->previousWeekStart, $this->previousWeekEnd])->sum('amount')
            + BuyGiftCardTransaction::whereBetween('created_at', [$this->previousWeekStart, $this->previousWeekEnd])->sum('amount')
            + VirtualCardTransaction::whereBetween('created_at', [$this->previousWeekStart, $this->previousWeekEnd])->sum('ngn_amount')
            + FundTransferRequest::whereBetween('created_at', [$this->previousWeekStart, $this->previousWeekEnd])->sum('amount')
            + FundTransaction::where('status', 1)->whereBetween('created_at', [$this->previousWeekStart, $this->previousWeekEnd])->sum('amount')
            + GiftCardTransaction::where('status', 1)->whereBetween('created_at', [$this->previousWeekStart, $this->previousWeekEnd])->sum(DB::raw('qty * card_value * rate'));


        // users
        $lastWeekUserCount = User::whereBetween('created_at', [$this->lastWeekStart, $this->lastWeekEnd])->count();
        $previousWeekUserCount = User::whereBetween('created_at', [$this->previousWeekStart, $this->previousWeekEnd])->count();

        // total revenuw
        $totalRevenue = BillTransaction::where('status', 1)->sum('amount')
        + BettingTransaction::where('status', 1)->sum('amount')
        + BuyGiftCardTransaction::sum('amount')
        + FundTransaction::where('status', 1)->sum('amount')
        + GiftCardTransaction::where('status', 1)->sum(DB::raw('qty * card_value * rate'));


        // Get revenue for last week
        $revenueLastWeek = BillTransaction::where('status', 1)->whereBetween('created_at', [$this->lastWeekStart, $this->lastWeekEnd])->sum('amount')
        + BettingTransaction::where('status', 1)->whereBetween('created_at', [$this->lastWeekStart, $this->lastWeekEnd])->sum('amount')
        + BuyGiftCardTransaction::whereBetween('created_at', [$this->lastWeekStart, $this->lastWeekEnd])->sum('amount')
        + FundTransaction::where('status', 1)->whereBetween('created_at', [$this->lastWeekStart, $this->lastWeekEnd])->sum('amount')
        + GiftCardTransaction::where('status', 1)->whereBetween('created_at', [$this->lastWeekStart, $this->lastWeekEnd])->sum(DB::raw('qty * card_value * rate'));

        // Get revenue for previous week
        $revenuePreviousWeek =
            BillTransaction::where('status', 1)->whereBetween('created_at', [$this->previousWeekStart, $this->previousWeekEnd])->sum('amount')
            + BettingTransaction::where('status', 1)->whereBetween('created_at', [$this->previousWeekStart, $this->previousWeekEnd])->sum('amount')
            + BuyGiftCardTransaction::whereBetween('created_at', [$this->previousWeekStart, $this->previousWeekEnd])->sum('amount')
            + FundTransaction::where('status', 1)->whereBetween('created_at', [$this->previousWeekStart, $this->previousWeekEnd])->sum('amount')
            + GiftCardTransaction::where('status', 1)->whereBetween('created_at', [$this->previousWeekStart, $this->previousWeekEnd])->sum(DB::raw('qty * card_value * rate'));


        // Expense
        $totalExpense = WalletTransactions::where('status', 1)->sum('amount');
        $totalExpenseLastWeek = WalletTransactions::whereBetween('created_at', [$this->lastWeekStart, $this->lastWeekEnd])->where('status', 1)->sum('amount');
        $totalExpensePrevousWeek = WalletTransactions::whereBetween('created_at', [$this->previousWeekStart, $this->previousWeekEnd])->where('status', 1)->sum('amount');


        $lastFiveTransactions = GiftCardTransaction::latest()->limit(5)->get();

        foreach ($lastFiveTransactions as $giftTran) {

            $giftCard = GiftCard::where('id', $giftTran->gift_card_id)->first();
            $giftcountry = GiftCardsCountry::where("id", $giftTran->gift_card_country_id)->first();
            $country = Country::where("id", $giftcountry->country_id)->first();
            $generalCountry = GeneralCountry::where("countryCode", strtoupper($country->iso))->first();
            $currency = Currencies::where("code", $generalCountry->currencyCode)->first();

            $giftTran->title = $currency->symbol . $giftTran->card_value . ' ' . $giftCard->title . ' ' . ($giftTran->qty == 1 ? "" : (" x" . ($giftTran->status == 0 ? $giftTran->qty : $giftTran->approved_qty)));
            $giftTran->total_amount = number_format((float) $giftTran->card_value * $giftTran->rate * ($giftTran->status == 0 ? ($giftTran->qty ?? 1) : $giftTran->approved_qty), 2);
            $giftTran->icon =  env('APP_URL') . $giftCard->brand_logo;
            $giftTran->type =  'giftcard';
            $giftTran->user =  User::where('id', $giftTran->user_id)->first()->first_name;
        }

        return response(
            [
                'stats' => [
                    'transaction' => [
                        'count' => $totalTransaction,
                        'difference' => $totalTransactionPreviousWeek > 0 ? ((($totalTransactionLastWeek - $totalTransactionPreviousWeek) / $totalTransactionPreviousWeek) * 100) : ($totalTransactionPreviousWeek > 0 ? 100 : 0),
                    ],
                    'users' => [
                        'count' => $this->userModel->all()->count(),
                        'difference' => $previousWeekUserCount > 0 ? ((($lastWeekUserCount - $previousWeekUserCount) / $previousWeekUserCount) * 100) : ($lastWeekUserCount > 0 ? 100 : 0),
                    ],
                    'revenue' => [
                        'count' => $totalRevenue,
                        'difference' => $revenuePreviousWeek > 0 ? ((($revenueLastWeek - $revenuePreviousWeek) / $revenuePreviousWeek) * 100) : ($revenuePreviousWeek > 0 ? 100 : 0),
                    ],
                    'expense' => [
                        'count' => $totalExpense,
                        'difference' => $totalExpensePrevousWeek > 0 ? ((($totalExpenseLastWeek - $totalExpensePrevousWeek) / $totalExpensePrevousWeek) * 100) : ($totalExpensePrevousWeek > 0 ? 100 : 0),
                    ],
                ],
                'gift_graph' => [
                    'daily' => GiftCardTransaction::getSalesForLast7Days(),
                    'weekly' => GiftCardTransaction::getSalesForLast4Weeks(),
                    'monthly' => GiftCardTransaction::getSalesByMonth(),
                ],
                'device' => UserDevice::selectRaw("
                    SUM(CASE WHEN os LIKE '%ios%' THEN 1 ELSE 0 END) as ios,
                    SUM(CASE WHEN os LIKE '%android%' THEN 1 ELSE 0 END) as android,
                    SUM(CASE WHEN os NOT LIKE '%ios%' AND os NOT LIKE '%android%' THEN 1 ELSE 0 END) as others
                ")->first(),
                'demographic' => DB::table('user_devices')->select('location', DB::raw('COUNT(*) as count'))->groupBy('location')->orderByDesc('count')->get(),
                'virtual_cards' => DB::table('maple_virtual_cards')->select('status', DB::raw('COUNT(*) as count'))->groupBy('status')->orderByDesc('count')->get(),
                'charges' => [
                    [
                        'title' => 'Withdrawal Charge',
                        'key' => 'withdrawal',
                        'amount' => 100,
                    ],
                    [
                        'title' => 'Deposit Charge',
                        'key' => 'deposit',
                        'amount' => 100,
                    ],
                    [
                        'title' => 'Dollar to Naira rate',
                        'key' => 'usd2naira',
                        'amount' => 1600,
                    ],
                    [
                        'title' => 'Naira to Dollar rate',
                        'key' => 'usd2naira',
                        'amount' => 1700,
                    ],
                    [
                        'title' => 'Buy Giftcard charge',
                        'key' => 'buy_giftcard',
                        'amount' => 200,
                    ],

                ],
                'recent_transaction' => $lastFiveTransactions
            ]
        );
    }
}
