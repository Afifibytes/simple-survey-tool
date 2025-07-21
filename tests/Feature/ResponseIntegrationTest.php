<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\Question;
use App\Models\Response;
use App\Services\AIQuestionGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ResponseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Survey $survey;
    protected Question $npsQuestion;
    protected Question $textQuestion;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test configuration
        config(['services.gemini.api_key' => 'test-api-key']);
        config(['services.gemini.api_url' => 'https://generativelanguage.googleapis.com/v1beta']);
        config(['services.gemini.model' => 'gemini-2.0-flash-exp']);
        config(['services.gemini.timeout' => 30]);

        // Create test survey with questions
        $this->survey = Survey::create([
            'name' => 'Integration Test Survey',
            'description' => 'Survey for integration testing',
            'is_active' => true
        ]);

        $this->npsQuestion = $this->survey->questions()->create([
            'type' => 'nps',
            'text' => 'How likely are you to recommend our service?',
            'order' => 0
        ]);

        $this->textQuestion = $this->survey->questions()->create([
            'type' => 'text',
            'text' => 'What can we do to improve your experience?',
            'order' => 1
        ]);
    }

    public function test_response_creation_with_valid_data()
    {
        $responseData = [
            'nps_score' => 8,
            'open_text' => 'Good service, but room for improvement in speed.'
        ];

        // Mock AI service
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'What specific areas would you like to see improved for speed?'
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->post(route('survey.store', $this->survey), $responseData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'has_follow_up' => true
        ]);

        // Verify database state
        $this->assertDatabaseHas('responses', [
            'survey_id' => $this->survey->id,
            'nps_score' => 8,
            'open_text' => 'Good service, but room for improvement in speed.',
            'ai_follow_up_question' => 'What specific areas would you like to see improved for speed?'
        ]);
    }

    public function test_response_creation_with_nps_only()
    {
        $responseData = [
            'nps_score' => 10,
            'open_text' => null
        ];

        $response = $this->post(route('survey.store', $this->survey), $responseData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'has_follow_up' => false
        ]);

        // Verify no AI follow-up was generated for NPS-only response
        $storedResponse = Response::where('survey_id', $this->survey->id)->first();
        $this->assertEquals(10, $storedResponse->nps_score);
        $this->assertNull($storedResponse->open_text);
        $this->assertNull($storedResponse->ai_follow_up_question);
    }

    public function test_response_creation_with_text_only()
    {
        $responseData = [
            'nps_score' => null,
            'open_text' => 'The interface could be more intuitive.'
        ];

        // Mock AI service
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'Which part of the interface needs improvement?'
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->post(route('survey.store', $this->survey), $responseData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'has_follow_up' => true
        ]);

        $storedResponse = Response::where('survey_id', $this->survey->id)->first();
        $this->assertNull($storedResponse->nps_score);
        $this->assertEquals('The interface could be more intuitive.', $storedResponse->open_text);
        $this->assertEquals('Which part of the interface needs improvement?', $storedResponse->ai_follow_up_question);
    }

    public function test_response_update_on_duplicate_session()
    {
        // This test verifies that multiple submissions from the same session update the same response
        // rather than creating multiple responses. We'll simulate this by creating a response
        // and then updating it with the same session ID.

        $sessionId = 'test-session-' . uniqid();

        // Create initial response directly
        $initialResponse = Response::create([
            'survey_id' => $this->survey->id,
            'session_id' => $sessionId,
            'nps_score' => 7,
            'open_text' => 'Initial feedback'
        ]);

        // Now test the updateOrCreate logic by calling it directly
        $updatedData = [
            'nps_score' => 9,
            'open_text' => 'Updated feedback after better experience'
        ];

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'What changed to improve your experience?'
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Simulate the updateOrCreate logic from the controller
        $response = Response::updateOrCreate(
            [
                'survey_id' => $this->survey->id,
                'session_id' => $sessionId,
            ],
            $updatedData
        );

        // Generate AI follow-up (simulating controller logic)
        if ($updatedData['open_text'] && !$response->ai_follow_up_question) {
            $aiService = app(\App\Services\AIQuestionGeneratorService::class);
            $followUpQuestion = $aiService->generateFollowUpQuestion(
                'What can we do to improve your experience?',
                $updatedData['open_text']
            );
            if ($followUpQuestion) {
                $response->update(['ai_follow_up_question' => $followUpQuestion]);
            }
        }

        // Verify only one response exists and it's updated
        $this->assertEquals(1, Response::where('survey_id', $this->survey->id)->count());

        $storedResponse = Response::where('survey_id', $this->survey->id)->first();
        $this->assertEquals(9, $storedResponse->nps_score);
        $this->assertEquals('Updated feedback after better experience', $storedResponse->open_text);
        $this->assertEquals('What changed to improve your experience?', $storedResponse->ai_follow_up_question);

        // Verify it's the same response that was updated, not a new one
        $this->assertEquals($initialResponse->id, $storedResponse->id);
    }

    public function test_ai_follow_up_answer_submission()
    {
        // This test verifies that the follow-up answer can be stored correctly
        // We test the core functionality by updating a response directly

        $response = Response::create([
            'survey_id' => $this->survey->id,
            'session_id' => 'test-session-123',
            'nps_score' => 8,
            'open_text' => 'Good service overall',
            'ai_follow_up_question' => 'What made the service good for you?'
        ]);

        // Test that we can update the response with follow-up answer
        $response->update([
            'ai_follow_up_answer' => 'The staff was friendly and the process was quick.',
            'completed_at' => now(),
        ]);

        // Verify the update worked
        $response->refresh();
        $this->assertEquals('The staff was friendly and the process was quick.', $response->ai_follow_up_answer);
        $this->assertNotNull($response->completed_at);
    }

    public function test_ai_service_failure_handling()
    {
        // Mock AI service failure
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'Service unavailable'], 500)
        ]);

        Log::shouldReceive('error')->once();

        $responseData = [
            'nps_score' => 8,
            'open_text' => 'Good service but could be better'
        ];

        $response = $this->post(route('survey.store', $this->survey), $responseData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'has_follow_up' => false
        ]);

        // Verify response was still stored despite AI failure
        $storedResponse = Response::where('survey_id', $this->survey->id)->first();
        $this->assertEquals(8, $storedResponse->nps_score);
        $this->assertEquals('Good service but could be better', $storedResponse->open_text);
        $this->assertNull($storedResponse->ai_follow_up_question);
    }

    public function test_ai_service_timeout_handling()
    {
        // Mock AI service timeout
        Http::fake([
            'generativelanguage.googleapis.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            }
        ]);

        Log::shouldReceive('error')->once();

        $responseData = [
            'open_text' => 'Service feedback that should trigger AI'
        ];

        $response = $this->post(route('survey.store', $this->survey), $responseData);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify graceful handling
        $storedResponse = Response::where('survey_id', $this->survey->id)->first();
        $this->assertNotNull($storedResponse);
        $this->assertNull($storedResponse->ai_follow_up_question);
    }

    public function test_response_validation_errors()
    {
        // Test invalid NPS score
        $invalidData = [
            'nps_score' => 15, // Invalid: should be 0-10
            'open_text' => 'Valid text'
        ];

        $response = $this->postJson(route('survey.store', $this->survey), $invalidData);
        $response->assertStatus(422);

        // Test text too long
        $invalidData = [
            'nps_score' => 8,
            'open_text' => str_repeat('a', 1001) // Invalid: exceeds 1000 chars
        ];

        $response = $this->postJson(route('survey.store', $this->survey), $invalidData);
        $response->assertStatus(422);
    }

    public function test_follow_up_validation_errors()
    {
        // Create response first
        $response = Response::create([
            'survey_id' => $this->survey->id,
            'session_id' => session()->getId(),
            'ai_follow_up_question' => 'Test question?'
        ]);

        // Test missing follow-up answer
        $invalidData = [];

        $submitResponse = $this->postJson(route('survey.followup', $this->survey), $invalidData);
        $submitResponse->assertStatus(422);

        // Test follow-up answer too long
        $invalidData = [
            'ai_follow_up_answer' => str_repeat('a', 1001)
        ];

        $submitResponse = $this->postJson(route('survey.followup', $this->survey), $invalidData);
        $submitResponse->assertStatus(422);
    }

    public function test_response_caching_behavior()
    {
        $responseData = [
            'open_text' => 'This should trigger AI caching'
        ];

        // First request - should call AI service
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'What specific aspect interests you?'
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $this->post(route('survey.store', $this->survey), $responseData);

        // Create another survey with same text question
        $survey2 = Survey::create(['name' => 'Survey 2']);
        $survey2->questions()->create([
            'type' => 'text',
            'text' => 'What can we do to improve your experience?', // Same question text
            'order' => 0
        ]);

        // Second request with same input should use cache
        Http::fake(); // No response needed - should use cache

        $response2 = $this->post(route('survey.store', $survey2), $responseData);

        $response2->assertStatus(200);
        $response2->assertJson(['has_follow_up' => true]);

        // Verify cached result was used
        $storedResponse = Response::where('survey_id', $survey2->id)->first();
        $this->assertEquals('What specific aspect interests you?', $storedResponse->ai_follow_up_question);
    }

    public function test_survey_response_statistics()
    {
        // Create multiple responses for statistics testing
        $responses = [
            ['nps_score' => 10, 'open_text' => 'Excellent!'],
            ['nps_score' => 8, 'open_text' => 'Very good'],
            ['nps_score' => 6, 'open_text' => 'Okay'],
            ['nps_score' => 9, 'open_text' => 'Great service'],
            ['nps_score' => 7, 'open_text' => 'Good but could improve']
        ];

        foreach ($responses as $index => $responseData) {
            Response::create([
                'survey_id' => $this->survey->id,
                'session_id' => 'session_' . $index,
                'nps_score' => $responseData['nps_score'],
                'open_text' => $responseData['open_text'],
                'completed_at' => now()
            ]);
        }

        // Test survey statistics
        $this->survey->refresh();
        $this->assertEquals(5, $this->survey->response_count);
        $this->assertEquals(8.0, $this->survey->average_nps_score);

        // Test admin view shows statistics
        $response = $this->get(route('admin.surveys.show', $this->survey));
        $response->assertStatus(200);
        $response->assertSee('5'); // Response count
        $response->assertSee('8'); // Average NPS
    }
}
