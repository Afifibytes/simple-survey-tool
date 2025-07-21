<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\Question;
use App\Models\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SurveySubmissionResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected Survey $survey;
    protected Question $textQuestion;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test survey with text question for AI follow-up
        $this->survey = Survey::create([
            'name' => 'Resilience Test Survey',
            'description' => 'Testing survey submission resilience'
        ]);

        $this->textQuestion = $this->survey->questions()->create([
            'type' => 'text',
            'text' => 'What can we improve?',
            'order' => 0
        ]);
    }

    public function test_survey_submission_succeeds_when_ai_service_is_unavailable()
    {
        // Mock AI service failure (service unavailable)
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'Service Unavailable'], 503)
        ]);

        Log::shouldReceive('error')->once()->with(
            'AI follow-up generation failed',
            \Mockery::type('array')
        );

        $responseData = [
            'open_text' => 'The service could be faster and more user-friendly.'
        ];

        $response = $this->post(route('survey.store', $this->survey), $responseData);

        // Verify survey submission succeeded despite AI failure
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'has_follow_up' => false // No follow-up due to AI failure
        ]);

        // Verify response was saved to database
        $this->assertDatabaseHas('responses', [
            'survey_id' => $this->survey->id,
            'open_text' => 'The service could be faster and more user-friendly.',
            'ai_follow_up_question' => null // No AI question generated
        ]);

        $storedResponse = Response::where('survey_id', $this->survey->id)->first();
        $this->assertNotNull($storedResponse);
        $this->assertEquals('The service could be faster and more user-friendly.', $storedResponse->open_text);
        $this->assertNull($storedResponse->ai_follow_up_question);
    }

    public function test_survey_submission_succeeds_when_ai_api_times_out()
    {
        // Mock AI service timeout
        Http::fake([
            'generativelanguage.googleapis.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout after 30 seconds');
            }
        ]);

        Log::shouldReceive('error')->once();

        $responseData = [
            'nps_score' => 8,
            'open_text' => 'Good service but response time could be better.'
        ];

        $response = $this->post(route('survey.store', $this->survey), $responseData);

        // Verify survey submission succeeded despite timeout
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'has_follow_up' => false
        ]);

        // Verify complete response was saved
        $this->assertDatabaseHas('responses', [
            'survey_id' => $this->survey->id,
            'nps_score' => 8,
            'open_text' => 'Good service but response time could be better.',
            'ai_follow_up_question' => null
        ]);
    }

    public function test_survey_submission_succeeds_when_ai_api_key_is_missing()
    {
        // Remove AI API key to simulate misconfiguration
        Config::set('services.gemini.api_key', null);

        Log::shouldReceive('warning')->once()->with('Gemini API key not configured');

        $responseData = [
            'open_text' => 'The interface is intuitive but could use more features.'
        ];

        $response = $this->post(route('survey.store', $this->survey), $responseData);

        // Verify survey submission succeeded without AI key
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'has_follow_up' => false
        ]);

        // Verify response was saved
        $storedResponse = Response::where('survey_id', $this->survey->id)->first();
        $this->assertNotNull($storedResponse);
        $this->assertEquals('The interface is intuitive but could use more features.', $storedResponse->open_text);
        $this->assertNull($storedResponse->ai_follow_up_question);
    }

    public function test_survey_submission_succeeds_when_ai_returns_invalid_response()
    {
        // Mock AI service returning invalid/malformed response
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => '' // Empty content
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $responseData = [
            'open_text' => 'The product works well but documentation could be clearer.'
        ];

        $response = $this->post(route('survey.store', $this->survey), $responseData);

        // Verify survey submission succeeded despite invalid AI response
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'has_follow_up' => false
        ]);

        // Verify response was saved without AI follow-up
        $this->assertDatabaseHas('responses', [
            'survey_id' => $this->survey->id,
            'open_text' => 'The product works well but documentation could be clearer.',
            'ai_follow_up_question' => null
        ]);
    }

    public function test_survey_submission_succeeds_when_ai_response_fails_validation()
    {
        // Mock AI service returning response that fails validation (no question mark)
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'This is not a proper question format'
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $responseData = [
            'open_text' => 'Overall satisfied but pricing could be more competitive.'
        ];

        $response = $this->post(route('survey.store', $this->survey), $responseData);

        // Verify survey submission succeeded despite validation failure
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'has_follow_up' => false
        ]);

        // Verify response was saved without invalid AI question
        $storedResponse = Response::where('survey_id', $this->survey->id)->first();
        $this->assertNotNull($storedResponse);
        $this->assertNull($storedResponse->ai_follow_up_question);
    }

    public function test_survey_submission_with_nps_only_works_without_ai()
    {
        // Test that NPS-only responses work fine (no AI generation attempted)
        $responseData = [
            'nps_score' => 9
            // No open_text, so no AI generation should be attempted
        ];

        $response = $this->post(route('survey.store', $this->survey), $responseData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'has_follow_up' => false
        ]);

        // Verify NPS response was saved
        $this->assertDatabaseHas('responses', [
            'survey_id' => $this->survey->id,
            'nps_score' => 9,
            'open_text' => null,
            'ai_follow_up_question' => null
        ]);
    }

    public function test_survey_submission_resilience_with_multiple_failures()
    {
        // Test multiple failure scenarios in sequence
        $testCases = [
            [
                'mock' => fn() => Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 500)]),
                'data' => ['open_text' => 'First response with AI failure'],
                'expected_text' => 'First response with AI failure'
            ],
            [
                'mock' => fn() => Config::set('services.gemini.api_key', ''),
                'data' => ['open_text' => 'Second response without API key'],
                'expected_text' => 'Second response without API key'
            ],
            [
                'mock' => fn() => Http::fake([
                    'generativelanguage.googleapis.com/*' => fn() => throw new \Exception('Network error')
                ]),
                'data' => ['nps_score' => 7, 'open_text' => 'Third response with network error'],
                'expected_text' => 'Third response with network error'
            ]
        ];

        Log::shouldReceive('error')->atLeast()->once(); // At least one AI failure expected
        Log::shouldReceive('warning')->atLeast()->once(); // At least one API key warning expected

        foreach ($testCases as $index => $testCase) {
            // Setup the failure scenario
            ($testCase['mock'])();

            // Submit response
            $response = $this->post(route('survey.store', $this->survey), $testCase['data']);

            // Verify success despite failure
            $response->assertStatus(200);
            $response->assertJson(['success' => true]);

            // Verify data was saved
            $this->assertDatabaseHas('responses', [
                'survey_id' => $this->survey->id,
                'open_text' => $testCase['expected_text']
            ]);

            // Clean up for next test
            Response::where('survey_id', $this->survey->id)->delete();
        }
    }

    public function test_successful_ai_generation_still_works()
    {
        // Verify that when AI service works, it still generates follow-up questions
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'What specific improvements would you suggest?'
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $responseData = [
            'open_text' => 'The service is good but has room for improvement.'
        ];

        $response = $this->post(route('survey.store', $this->survey), $responseData);

        // Verify successful AI generation
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'has_follow_up' => true
        ]);

        // Verify AI question was generated and saved
        $storedResponse = Response::where('survey_id', $this->survey->id)->first();
        $this->assertNotNull($storedResponse->ai_follow_up_question);
        $this->assertEquals('What specific improvements would you suggest?', $storedResponse->ai_follow_up_question);
    }
}
