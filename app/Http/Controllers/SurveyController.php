<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SurveyController extends Controller
{
    public function index(): View
    {
        $surveys = Survey::with('questions')
            ->withCount('responses')
            ->latest()
            ->paginate(12);

        return view('admin.surveys.index', compact('surveys'));
    }

    public function create(): View
    {
        return view('admin.surveys.create');
    }

    public function store(StoreSurveyRequest $request): RedirectResponse
    {
        $survey = Survey::create($request->validated());

        // Create questions
        foreach ($request->questions as $index => $questionData) {
            $survey->questions()->create([
                'type' => $questionData['type'],
                'text' => $questionData['text'],
                'order' => $index,
                'options' => $questionData['options'] ?? null,
            ]);
        }

        return redirect()
            ->route('admin.surveys.show', $survey)
            ->with('success', 'Survey created successfully.');
    }

    public function show(Survey $survey): View
    {
        $survey->load(['questions', 'responses' => function ($query) {
            $query->completed()->latest()->limit(10);
        }]);

        return view('admin.surveys.show', compact('survey'));
    }

    public function edit(Survey $survey): View
    {
        $survey->load('questions');
        return view('admin.surveys.edit', compact('survey'));
    }

    public function update(UpdateSurveyRequest $request, Survey $survey): RedirectResponse
    {
        $survey->update($request->validated());

        // Update questions
        $survey->questions()->delete();
        foreach ($request->questions as $index => $questionData) {
            $survey->questions()->create([
                'type' => $questionData['type'],
                'text' => $questionData['text'],
                'order' => $index,
                'options' => $questionData['options'] ?? null,
            ]);
        }

        return redirect()
            ->route('admin.surveys.show', $survey)
            ->with('success', 'Survey updated successfully.');
    }

    public function destroy(Survey $survey): RedirectResponse
    {
        $survey->delete();

        return redirect()
            ->route('admin.surveys.index')
            ->with('success', 'Survey deleted successfully.');
    }

    public function responses(Survey $survey): View
    {
        $responses = $survey->responses()
            ->completed()
            ->latest()
            ->paginate(20);

        return view('admin.surveys.responses', compact('survey', 'responses'));
    }
}
