<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\Response;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $totalSurveys = Survey::count();
        $activeSurveys = Survey::active()->count();
        $totalResponses = Response::count(); // Show all responses, not just completed
        $completedResponses = Response::completed()->count();
        $completionRate = $totalResponses > 0 ? ($completedResponses / $totalResponses) * 100 : 0;

        $recentSurveys = Survey::with('questions')
            ->withCount('responses') // This will count all responses
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalSurveys',
            'activeSurveys',
            'totalResponses',
            'completedResponses',
            'completionRate',
            'recentSurveys'
        ));
    }
}
