<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AIQuestionGeneratorService
{
    private ?string $apiKey;
    private string $apiUrl;
    private string $model;
    private int $timeout;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->apiUrl = config('services.gemini.api_url', 'https://generativelanguage.googleapis.com/v1beta');
        $this->model = config('services.gemini.model', 'gemini-2.0-flash-exp');
        $this->timeout = config('services.gemini.timeout', 30);
    }

    public function generateFollowUpQuestion(string $originalQuestion, string $response): ?string
    {
        // Check cache first
        $cacheKey = $this->getCacheKey($originalQuestion, $response);
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            $prompt = $this->buildPrompt($originalQuestion, $response);
            $followUpQuestion = $this->callGemini($prompt);

            if ($followUpQuestion && $this->validateQuestion($followUpQuestion)) {
                // Cache successful results
                Cache::put($cacheKey, $followUpQuestion, now()->addHours(24));
                return $followUpQuestion;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('AI question generation failed', [
                'original_question' => $originalQuestion,
                'response' => $response,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function buildPrompt(string $originalQuestion, string $response): string
    {
        return "You are a survey follow-up question generator. Your task is to create a single, relevant follow-up question based on a respondent's answer.

Original Question: \"{$originalQuestion}\"
Respondent's Answer: \"{$response}\"

Guidelines:
- Create ONE follow-up question that digs deeper into their response
- Keep it conversational and engaging
- Make it specific to their answer, not generic
- Limit to 20 words or less
- Don't repeat information they already provided
- Focus on understanding their perspective better

Follow-up Question:";
    }

    private function callGemini(string $prompt): ?string
    {
        if (!$this->apiKey) {
            Log::warning('Gemini API key not configured');
            return null;
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])
        ->timeout($this->timeout)
        ->post($this->apiUrl . '/models/' . $this->model . ':generateContent?key=' . $this->apiKey, [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 100,
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
        }

        throw new \Exception('Gemini API call failed: ' . $response->body());
    }

    private function validateQuestion(string $question): bool
    {
        // Basic validation rules
        $wordCount = str_word_count($question);

        return $wordCount >= 3
            && $wordCount <= 25
            && str_ends_with($question, '?')
            && strlen($question) <= 200;
    }

    private function getCacheKey(string $originalQuestion, string $response): string
    {
        return 'ai_followup_' . md5($originalQuestion . '|' . $response);
    }
}
