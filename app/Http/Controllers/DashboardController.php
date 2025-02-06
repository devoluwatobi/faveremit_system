<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

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
        } elseif($request->input('type') == 'monthly') {
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

}
