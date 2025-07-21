<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FrontendApiResponseTest extends TestCase
{
    use RefreshDatabase;

    protected Survey $survey;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test survey
        $this->survey = Survey::create([
            'name' => 'Frontend Test Survey',
            'description' => 'Testing frontend API responses'
        ]);

        $this->survey->questions()->create([
            'type' => 'text',
            'text' => 'What can we improve?',
            'order' => 0
        ]);
    }

    public function test_api_returns_proper_json_response_when_ai_key_missing()
    {
        // Remove AI API key
        Config::set('services.openai.api_key', null);
        
        Log::shouldReceive('warning')->once();

        $responseData = [
            'open_text' => 'The service could be better'
        ];

        $response = $this->post(route('survey.store', $this->survey), $responseData);

        // Verify HTTP status is 200 (success)
        $response->assertStatus(200);
        
        // Verify response structure matches what frontend expects
        $response->assertJson([
            'success' => true,
            'has_follow_up' => false
        ]);

        // Verify response has the expected structure
        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertFalse($responseData['has_follow_up']);
        $this->assertArrayHasKey('response', $responseData);
        $this->assertIsArray($responseData['response']);
    }

    public function test_api_returns_proper_json_response_with_working_ai()
    {
        // Ensure AI key is set
        Config::set('services.openai.api_key', 'test-key');
        
        // Mock successful AI response
        \Illuminate\Support\Facades\Http::fake([
            'api.openai.com/*' => \Illuminate\Support\Facades\Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'What specific improvements would you suggest?'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $responseData = [
            'open_text' => 'The service could be better'
        ];

        $response = $this->post(route('survey.store', $this->survey), $responseData);

        // Verify HTTP status is 200 (success)
        $response->assertStatus(200);
        
        // Verify response structure with AI follow-up
        $response->assertJson([
            'success' => true,
            'has_follow_up' => true
        ]);

        $responseData = $response->json();
        $this->assertTrue($responseData['success']);
        $this->assertTrue($responseData['has_follow_up']);
        $this->assertArrayHasKey('response', $responseData);
        $this->assertNotNull($responseData['response']['ai_follow_up_question']);
    }

    public function test_api_response_structure_is_consistent()
    {
        // Test with no AI key
        Config::set('services.openai.api_key', null);
        Log::shouldReceive('warning')->once();

        $response1 = $this->post(route('survey.store', $this->survey), [
            'open_text' => 'Test response 1'
        ]);

        // Test with AI key but service failure
        Config::set('services.openai.api_key', 'test-key');
        \Illuminate\Support\Facades\Http::fake([
            'api.openai.com/*' => \Illuminate\Support\Facades\Http::response([], 500)
        ]);
        Log::shouldReceive('error')->once();

        $response2 = $this->post(route('survey.store', $this->survey), [
            'open_text' => 'Test response 2'
        ]);

        // Both responses should have the same structure
        $data1 = $response1->json();
        $data2 = $response2->json();

        // Both should be successful HTTP responses
        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Both should have consistent JSON structure
        $this->assertArrayHasKey('success', $data1);
        $this->assertArrayHasKey('success', $data2);
        $this->assertArrayHasKey('has_follow_up', $data1);
        $this->assertArrayHasKey('has_follow_up', $data2);
        $this->assertArrayHasKey('response', $data1);
        $this->assertArrayHasKey('response', $data2);

        // Both should indicate success
        $this->assertTrue($data1['success']);
        $this->assertTrue($data2['success']);

        // Both should indicate no follow-up (due to failures)
        $this->assertFalse($data1['has_follow_up']);
        $this->assertFalse($data2['has_follow_up']);
    }

    public function test_validation_errors_return_proper_format()
    {
        // Test with invalid data (NPS score out of range)
        $response = $this->post(route('survey.store', $this->survey), [
            'nps_score' => 15 // Invalid: should be 0-10
        ]);

        // Should return validation error
        $response->assertStatus(422);
        
        // Should have validation error structure
        $response->assertJsonStructure([
            'message',
            'errors'
        ]);
    }

    public function test_nps_only_response_works_correctly()
    {
        $response = $this->post(route('survey.store', $this->survey), [
            'nps_score' => 8
            // No open_text, so no AI generation attempted
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'has_follow_up' => false
        ]);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertFalse($data['has_follow_up']);
        $this->assertEquals(8, $data['response']['nps_score']);
        $this->assertNull($data['response']['open_text']);
    }
}
