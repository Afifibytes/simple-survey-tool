<?php

namespace Tests\Unit;

use App\Services\AIQuestionGeneratorService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AIQuestionGeneratorServiceTest extends TestCase
{
    private AIQuestionGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test configuration
        Config::set('services.openai.api_key', 'test-api-key');
        Config::set('services.openai.api_url', 'https://api.openai.com/v1');
        Config::set('services.openai.timeout', 30);

        $this->service = new AIQuestionGeneratorService();
    }

    public function test_generates_follow_up_question_successfully()
    {
        // Mock successful OpenAI API response
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'What specific features would you like to see improved?'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $originalQuestion = "How satisfied are you with our product?";
        $response = "It's good but could be better";

        $result = $this->service->generateFollowUpQuestion($originalQuestion, $response);

        $this->assertEquals('What specific features would you like to see improved?', $result);
    }

    public function test_returns_cached_result_when_available()
    {
        $originalQuestion = "How satisfied are you with our product?";
        $response = "It's good but could be better";
        $cachedQuestion = "What would make it better?";

        // Set up cache
        Cache::shouldReceive('get')
            ->once()
            ->with('ai_followup_' . md5($originalQuestion . '|' . $response))
            ->andReturn($cachedQuestion);

        $result = $this->service->generateFollowUpQuestion($originalQuestion, $response);

        $this->assertEquals($cachedQuestion, $result);
    }

    public function test_caches_successful_results()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'What specific features would you like to see improved?'
                        ]
                    ]
                ]
            ], 200)
        ]);

        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')
            ->once()
            ->with(
                'ai_followup_' . md5("test question|test response"),
                'What specific features would you like to see improved?',
                \Mockery::type('Illuminate\Support\Carbon')
            );

        $this->service->generateFollowUpQuestion("test question", "test response");
    }

    public function test_returns_null_when_api_key_not_configured()
    {
        Config::set('services.openai.api_key', null);
        $service = new AIQuestionGeneratorService();

        Log::shouldReceive('warning')
            ->once()
            ->with('OpenAI API key not configured');

        Cache::shouldReceive('get')->once()->andReturn(null);

        $result = $service->generateFollowUpQuestion("test", "test");

        $this->assertNull($result);
    }

    public function test_handles_api_failure_gracefully()
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'API Error'], 500)
        ]);

        Cache::shouldReceive('get')->once()->andReturn(null);
        Log::shouldReceive('error')
            ->once()
            ->with('AI question generation failed', \Mockery::type('array'));

        $result = $this->service->generateFollowUpQuestion("test", "test");

        $this->assertNull($result);
    }

    public function test_validates_valid_generated_questions()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'What specific features would you like to see improved?'
                        ]
                    ]
                ]
            ], 200)
        ]);

        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $result = $this->service->generateFollowUpQuestion("test", "test");
        $this->assertNotNull($result);
    }

    public function test_rejects_invalid_generated_questions()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'This is not a question'
                        ]
                    ]
                ]
            ], 200)
        ]);

        Cache::shouldReceive('get')->once()->andReturn(null);

        $result = $this->service->generateFollowUpQuestion("test", "test");
        $this->assertNull($result);
    }

    public function test_builds_correct_prompt()
    {
        $originalQuestion = "How satisfied are you?";
        $response = "Very satisfied";

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'What made you most satisfied?'
                        ]
                    ]
                ]
            ], 200)
        ]);

        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $this->service->generateFollowUpQuestion($originalQuestion, $response);

        Http::assertSent(function ($request) use ($originalQuestion, $response) {
            $body = json_decode($request->body(), true);
            $prompt = $body['messages'][0]['content'];

            return str_contains($prompt, $originalQuestion) &&
                   str_contains($prompt, $response) &&
                   str_contains($prompt, 'survey follow-up question generator');
        });
    }

    public function test_uses_correct_openai_parameters()
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'What made you most satisfied?'
                        ]
                    ]
                ]
            ], 200)
        ]);

        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once();

        $this->service->generateFollowUpQuestion("test", "test");

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);

            return $body['model'] === 'gpt-3.5-turbo' &&
                   $body['max_tokens'] === 100 &&
                   $body['temperature'] === 0.7 &&
                   isset($body['messages']) &&
                   count($body['messages']) === 1 &&
                   $body['messages'][0]['role'] === 'user';
        });
    }

    public function test_question_validation_rules()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validateQuestion');
        $method->setAccessible(true);

        // Valid questions
        $this->assertTrue($method->invoke($this->service, 'What do you think?'));
        $this->assertTrue($method->invoke($this->service, 'How can we improve our service quality?'));

        // Invalid questions
        $this->assertFalse($method->invoke($this->service, 'No?')); // Too short
        $this->assertFalse($method->invoke($this->service, 'This is not a question')); // No question mark
        $this->assertFalse($method->invoke($this->service, str_repeat('What ', 30) . '?')); // Too long
        $this->assertFalse($method->invoke($this->service, str_repeat('a', 201) . '?')); // Too many characters
    }

    public function test_cache_key_generation()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('getCacheKey');
        $method->setAccessible(true);

        $key1 = $method->invoke($this->service, 'question1', 'response1');
        $key2 = $method->invoke($this->service, 'question2', 'response2');
        $key3 = $method->invoke($this->service, 'question1', 'response1');

        $this->assertStringStartsWith('ai_followup_', $key1);
        $this->assertNotEquals($key1, $key2);
        $this->assertEquals($key1, $key3); // Same inputs should generate same key
    }
}
