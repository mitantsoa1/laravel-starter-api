<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $totalUsers = User::count();
        $newUsersToday = User::whereDate('created_at', now()->toDateString())->count();
        
        // Simple growth calculation (this week vs last week)
        $thisWeek = User::where('created_at', '>=', now()->startOfWeek())->count();
        $lastWeek = User::whereBetween('created_at', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek()
        ])->count();
        
        $growth = 0;
        if ($lastWeek > 0) {
            $growth = (($thisWeek - $lastWeek) / $lastWeek) * 100;
        } elseif ($thisWeek > 0) {
            $growth = 100;
        }

        return response()->json([
            'stats' => [
                'total_users' => $totalUsers,
                'new_users_today' => $newUsersToday,
                'weekly_growth' => round($growth, 2),
                'active_sessions' => DB::table('sessions')->count(),
            ],
            'recent_users' => User::latest()->take(5)->get(['id', 'name', 'email', 'created_at']),
        ]);
    }
}
