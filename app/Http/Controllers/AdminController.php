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
        $totalResponses = Response::completed()->count();
        $recentSurveys = Survey::with('questions')
            ->withCount('responses')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalSurveys',
            'activeSurveys', 
            'totalResponses',
            'recentSurveys'
        ));
    }
}
