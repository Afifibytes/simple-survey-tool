<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResponseRequest;
use App\Models\Survey;
use App\Models\Response;
use App\Services\AIQuestionGeneratorService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ResponseController extends Controller
{
    public function __construct(
        private AIQuestionGeneratorService $aiService
    ) {}

    public function show(Survey $survey): View
    {
        // Check if survey is active
        if (!$survey->is_active) {
            abort(404, 'This survey is not currently available.');
        }

        $survey->load('questions');
        $sessionId = session()->getId();

        $existingResponse = Response::where('survey_id', $survey->id)
            ->where('session_id', $sessionId)
            ->first();

        return view('survey.show', compact('survey', 'existingResponse'));
    }

    public function store(StoreResponseRequest $request, Survey $survey): JsonResponse
    {
        // Check if survey is active
        if (!$survey->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This survey is not currently accepting responses.'
            ], 403);
        }

        $sessionId = session()->getId();

        $response = Response::where('id', $request->response_id)
            ->where('survey_id', $survey->id)
            ->where('session_id', $sessionId)
            ->first();

        if ($response) {
            $response->update($request->validated());
        } else {
            // Response not found or doesn't belong to this session, create new one
            $response = Response::create((array_merge([
                'survey_id' => $survey->id,
                'session_id' => $sessionId,
            ], $request->validated())));
        }

        // Generate AI follow-up question if open text is provided
        if ($request->open_text && !$response->ai_follow_up_question) {
            $this->generateAiFollowUp($response, $request->open_text);
            $response->refresh();
        }

        // Mark as completed if no AI follow-up question was generated
        if (!$response->ai_follow_up_question) {
            $response->update(['completed_at' => now()]);
        }

        $jsonResponse = [
            'success' => true,
            'response' => $response,
            'response_id' => $response->id,
            'has_follow_up' => !is_null($response->ai_follow_up_question),
        ];


        return response()->json($jsonResponse);
    }

    public function storeFollowUp(Request $request, Survey $survey): JsonResponse
    {

        // Check if survey is active
        if (!$survey->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This survey is not currently accepting responses.'
            ], 403);
        }

        $request->validate([
            'response_id' => 'nullable|integer|exists:responses,id',
            'ai_follow_up_answer' => 'required|string|max:1000',
        ]);

        $sessionId = session()->getId();

        $response = Response::where('id', $request->response_id)
            ->where('survey_id', $survey->id)
            ->where('session_id', $sessionId)
            ->first();

        $response->update([
            'ai_follow_up_answer' => $request->ai_follow_up_answer,
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for completing the survey!',
        ]);
    }

    private function generateAiFollowUp(Response $response, string $openText): void
    {
        try {
            $followUpQuestion = $this->aiService->generateFollowUpQuestion(
                $response->survey->questions()->where('type', 'text')->first()->text,
                $openText
            );

            if ($followUpQuestion) {
                $response->update(['ai_follow_up_question' => $followUpQuestion]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the response submission
            logger()->error('AI follow-up generation failed', [
                'response_id' => $response->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
