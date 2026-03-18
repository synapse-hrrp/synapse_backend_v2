<?php

namespace Modules\Pharmacie\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Pharmacie\App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    /**
     * Dashboard complet
     */
    public function index(): JsonResponse
    {
        $dashboard = $this->dashboardService->getDashboard();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard Pharmacie',
            'data' => $dashboard
        ], 200);
    }
}