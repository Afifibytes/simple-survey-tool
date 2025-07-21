<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\Question;
use App\Models\Response;
use App\Services\AIQuestionGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SurveyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test configuration for AI service
        config(['services.gemini.api_key' => 'test-api-key']);
        config(['services.gemini.api_url' => 'https://generativelanguage.googleapis.com/v1beta']);
        config(['services.gemini.model' => 'gemini-2.0-flash-exp']);
        config(['services.gemini.timeout' => 30]);
    }

    public function test_complete_survey_creation_flow()
    {
        // Test creating a survey with questions
        $surveyData = [
            'name' => 'Customer Satisfaction Survey',
            'description' => 'Help us improve our service',
            'questions' => [
                [
                    'type' => 'nps',
                    'text' => 'How likely are you to recommend our service?',
                    'options' => null
                ],
                [
                    'type' => 'text',
                    'text' => 'What can we do to improve?',
                    'options' => null
                ]
            ]
        ];

        $response = $this->post(route('admin.surveys.store'), $surveyData);

        // Verify redirect and database state
        $response->assertRedirect();

        $this->assertDatabaseHas('surveys', [
            'name' => 'Customer Satisfaction Survey',
            'description' => 'Help us improve our service'
        ]);

        $survey = Survey::where('name', 'Customer Satisfaction Survey')->first();
        $this->assertNotNull($survey);
        $this->assertEquals(2, $survey->questions()->count());

        // Verify questions were created correctly
        $npsQuestion = $survey->questions()->where('type', 'nps')->first();
        $textQuestion = $survey->questions()->where('type', 'text')->first();

        $this->assertNotNull($npsQuestion);
        $this->assertNotNull($textQuestion);
        $this->assertEquals('How likely are you to recommend our service?', $npsQuestion->text);
        $this->assertEquals('What can we do to improve?', $textQuestion->text);
    }

    public function test_survey_update_flow()
    {
        // Create initial survey
        $survey = Survey::create([
            'name' => 'Original Survey',
            'description' => 'Original description'
        ]);

        $survey->questions()->create([
            'type' => 'nps',
            'text' => 'Original NPS question?',
            'order' => 0
        ]);

        // Update survey
        $updateData = [
            'name' => 'Updated Survey',
            'description' => 'Updated description',
            'questions' => [
                [
                    'type' => 'nps',
                    'text' => 'Updated NPS question?',
                    'options' => null
                ],
                [
                    'type' => 'text',
                    'text' => 'New text question?',
                    'options' => null
                ]
            ]
        ];

        $response = $this->put(route('admin.surveys.update', $survey), $updateData);

        $response->assertRedirect();

        // Verify database updates
        $survey->refresh();
        $this->assertEquals('Updated Survey', $survey->name);
        $this->assertEquals('Updated description', $survey->description);
        $this->assertEquals(2, $survey->questions()->count());

        $updatedNpsQuestion = $survey->questions()->where('type', 'nps')->first();
        $newTextQuestion = $survey->questions()->where('type', 'text')->first();

        $this->assertEquals('Updated NPS question?', $updatedNpsQuestion->text);
        $this->assertEquals('New text question?', $newTextQuestion->text);
    }

    public function test_survey_deletion_cascade()
    {
        // Create survey with questions and responses
        $survey = Survey::create([
            'name' => 'Test Survey',
            'description' => 'Test description'
        ]);

        $npsQuestion = $survey->questions()->create([
            'type' => 'nps',
            'text' => 'NPS Question?',
            'order' => 0
        ]);

        $textQuestion = $survey->questions()->create([
            'type' => 'text',
            'text' => 'Text Question?',
            'order' => 1
        ]);

        $response = Response::create([
            'survey_id' => $survey->id,
            'session_id' => 'test-session',
            'nps_score' => 8,
            'open_text' => 'Great service!',
            'completed_at' => now()
        ]);

        // Delete survey
        $deleteResponse = $this->delete(route('admin.surveys.destroy', $survey));
        $deleteResponse->assertRedirect();

        // Verify cascade deletion
        $this->assertDatabaseMissing('surveys', ['id' => $survey->id]);
        $this->assertDatabaseMissing('questions', ['id' => $npsQuestion->id]);
        $this->assertDatabaseMissing('questions', ['id' => $textQuestion->id]);
        $this->assertDatabaseMissing('responses', ['id' => $response->id]);
    }

    public function test_complete_response_submission_flow()
    {
        // Create survey with questions
        $survey = Survey::create([
            'name' => 'Test Survey',
            'description' => 'Test description'
        ]);

        $npsQuestion = $survey->questions()->create([
            'type' => 'nps',
            'text' => 'How likely are you to recommend us?',
            'order' => 0
        ]);

        $textQuestion = $survey->questions()->create([
            'type' => 'text',
            'text' => 'What can we improve?',
            'order' => 1
        ]);

        // Mock AI service response
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

        // Submit response
        $responseData = [
            'nps_score' => 9,
            'open_text' => 'The service is excellent but could be faster.'
        ];

        $response = $this->post(route('survey.store', $survey), $responseData);

        $response->assertJson(['success' => true]);

        // Verify response was stored
        $this->assertDatabaseHas('responses', [
            'survey_id' => $survey->id,
            'nps_score' => 9,
            'open_text' => 'The service is excellent but could be faster.'
        ]);

        $storedResponse = Response::where('survey_id', $survey->id)->first();
        $this->assertNotNull($storedResponse->ai_follow_up_question);
        $this->assertEquals('What specific improvements would you suggest?', $storedResponse->ai_follow_up_question);
    }

    public function test_ai_integration_end_to_end_flow()
    {
        // Create survey with text question for AI follow-up
        $survey = Survey::create(['name' => 'AI Integration Test']);

        $survey->questions()->create([
            'type' => 'text',
            'text' => 'What can we improve?',
            'order' => 0
        ]);

        // Mock AI service response
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => 'What specific improvements would help most?'
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        // Submit initial response
        $responseData = [
            'open_text' => 'The service is good but could be faster'
        ];

        $response = $this->post(route('survey.store', $survey), $responseData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'has_follow_up' => true
        ]);

        // Verify AI follow-up was generated and stored
        $storedResponse = Response::where('survey_id', $survey->id)->first();
        $this->assertNotNull($storedResponse);
        $this->assertEquals('The service is good but could be faster', $storedResponse->open_text);
        $this->assertEquals('What specific improvements would help most?', $storedResponse->ai_follow_up_question);
        $this->assertNull($storedResponse->completed_at); // Response is NOT completed yet - waiting for AI follow-up answer

        // Test that AI service was called with correct parameters
        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return isset($body['contents']) &&
                   isset($body['generationConfig']) &&
                   $body['generationConfig']['temperature'] === 0.7 &&
                   $body['generationConfig']['maxOutputTokens'] === 100;
        });
    }

    public function test_response_submission_without_ai_service()
    {
        // Disable AI service by removing API key
        config(['services.gemini.api_key' => null]);

        $survey = Survey::create(['name' => 'Test Survey']);

        $survey->questions()->create([
            'type' => 'text',
            'text' => 'What can we improve?',
            'order' => 0
        ]);

        $responseData = [
            'open_text' => 'The service is good but could be better.'
        ];

        $response = $this->post(route('survey.store', $survey), $responseData);

        $response->assertJson(['success' => true]);

        // Verify response was stored without AI follow-up
        $storedResponse = Response::where('survey_id', $survey->id)->first();
        $this->assertNotNull($storedResponse);
        $this->assertNull($storedResponse->ai_follow_up_question);
    }

    public function test_survey_statistics_calculation()
    {
        $survey = Survey::create(['name' => 'Test Survey']);

        // Create multiple responses
        Response::create([
            'survey_id' => $survey->id,
            'session_id' => 'session1',
            'nps_score' => 9,
            'completed_at' => now()
        ]);

        Response::create([
            'survey_id' => $survey->id,
            'session_id' => 'session2',
            'nps_score' => 7,
            'completed_at' => now()
        ]);

        Response::create([
            'survey_id' => $survey->id,
            'session_id' => 'session3',
            'nps_score' => 10,
            'completed_at' => now()
        ]);

        // Test calculated attributes
        $survey->refresh();
        $this->assertEquals(3, $survey->response_count);
        $this->assertEquals(8.67, round($survey->average_nps_score, 2));
    }

    public function test_survey_view_with_responses()
    {
        $survey = Survey::create(['name' => 'Test Survey']);

        $survey->questions()->create([
            'type' => 'nps',
            'text' => 'How likely are you to recommend us?',
            'order' => 0
        ]);

        // Create some responses
        Response::create([
            'survey_id' => $survey->id,
            'session_id' => 'session1',
            'nps_score' => 9,
            'open_text' => 'Great service!',
            'completed_at' => now()
        ]);

        $response = $this->get(route('admin.surveys.show', $survey));

        $response->assertStatus(200);
        $response->assertViewHas('survey');
        $response->assertSee('Test Survey');
        $response->assertSee('Great service!');
    }

    public function test_public_survey_display()
    {
        $survey = Survey::create(['name' => 'Public Test Survey']);

        $survey->questions()->create([
            'type' => 'nps',
            'text' => 'Rate our service',
            'order' => 0
        ]);

        $survey->questions()->create([
            'type' => 'text',
            'text' => 'Any comments?',
            'order' => 1
        ]);

        $response = $this->get(route('survey.show', $survey));

        $response->assertStatus(200);
        $response->assertSee('Public Test Survey');
        $response->assertSee('Rate our service');
        $response->assertSee('Any comments?');
    }
}
