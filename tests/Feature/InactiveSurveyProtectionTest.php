<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\Question;
use App\Models\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveSurveyProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected Survey $activeSurvey;
    protected Survey $inactiveSurvey;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create active survey
        $this->activeSurvey = Survey::create([
            'name' => 'Active Survey',
            'description' => 'This survey is active',
            'is_active' => true
        ]);

        $this->activeSurvey->questions()->create([
            'type' => 'nps',
            'text' => 'How satisfied are you?',
            'order' => 0
        ]);

        $this->activeSurvey->questions()->create([
            'type' => 'text',
            'text' => 'Any feedback?',
            'order' => 1
        ]);

        // Create inactive survey
        $this->inactiveSurvey = Survey::create([
            'name' => 'Inactive Survey',
            'description' => 'This survey is inactive',
            'is_active' => false
        ]);

        $this->inactiveSurvey->questions()->create([
            'type' => 'nps',
            'text' => 'How satisfied are you?',
            'order' => 0
        ]);

        $this->inactiveSurvey->questions()->create([
            'type' => 'text',
            'text' => 'Any feedback?',
            'order' => 1
        ]);
    }

    public function test_active_survey_can_be_viewed()
    {
        $response = $this->get(route('survey.show', $this->activeSurvey));

        $response->assertStatus(200);
        $response->assertSee('Active Survey');
        $response->assertSee('How satisfied are you?');
        $response->assertSee('Any feedback?');
    }

    public function test_inactive_survey_cannot_be_viewed()
    {
        $response = $this->get(route('survey.show', $this->inactiveSurvey));

        $response->assertStatus(404);
    }

    public function test_inactive_survey_shows_proper_error_message()
    {
        $response = $this->get(route('survey.show', $this->inactiveSurvey));

        $response->assertStatus(404);
        // The 404 page should be shown, but we can't easily test the exact message
        // since it depends on the 404 view template
    }

    public function test_active_survey_accepts_responses()
    {
        $responseData = [
            'nps_score' => 8,
            'open_text' => 'Great service!'
        ];

        $response = $this->post(route('survey.store', $this->activeSurvey), $responseData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        // Verify response was stored
        $this->assertDatabaseHas('responses', [
            'survey_id' => $this->activeSurvey->id,
            'nps_score' => 8,
            'open_text' => 'Great service!'
        ]);
    }

    public function test_inactive_survey_rejects_responses()
    {
        $responseData = [
            'nps_score' => 8,
            'open_text' => 'Great service!'
        ];

        $response = $this->post(route('survey.store', $this->inactiveSurvey), $responseData);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'This survey is not currently accepting responses.'
        ]);

        // Verify no response was stored
        $this->assertDatabaseMissing('responses', [
            'survey_id' => $this->inactiveSurvey->id
        ]);
    }

    public function test_inactive_survey_rejects_follow_up_responses()
    {
        // First create a response in the database (simulating what would happen
        // if survey was deactivated after initial response but before follow-up)
        $existingResponse = Response::create([
            'survey_id' => $this->inactiveSurvey->id,
            'session_id' => session()->getId(),
            'open_text' => 'Initial response',
            'ai_follow_up_question' => 'What would you improve?'
        ]);

        $followUpData = [
            'ai_follow_up_answer' => 'Better customer service'
        ];

        $response = $this->post(route('survey.followup', $this->inactiveSurvey), $followUpData);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'This survey is not currently accepting responses.'
        ]);

        // Verify follow-up answer was not stored
        $existingResponse->refresh();
        $this->assertNull($existingResponse->ai_follow_up_answer);
        $this->assertNull($existingResponse->completed_at);
    }

    public function test_survey_deactivation_prevents_new_responses()
    {
        // First, verify survey accepts responses when active
        $responseData = [
            'nps_score' => 9,
            'open_text' => 'Excellent!'
        ];

        $response = $this->post(route('survey.store', $this->activeSurvey), $responseData);
        $response->assertStatus(200);

        // Now deactivate the survey
        $this->activeSurvey->update(['is_active' => false]);

        // Try to submit another response
        $newResponseData = [
            'nps_score' => 7,
            'open_text' => 'Good but could be better'
        ];

        $response = $this->post(route('survey.store', $this->activeSurvey), $newResponseData);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'This survey is not currently accepting responses.'
        ]);

        // Verify only the first response exists
        $this->assertEquals(1, Response::where('survey_id', $this->activeSurvey->id)->count());
    }

    public function test_survey_reactivation_allows_responses_again()
    {
        // Start with inactive survey
        $this->assertFalse($this->inactiveSurvey->is_active);

        // Verify it rejects responses
        $responseData = [
            'nps_score' => 8,
            'open_text' => 'Test response'
        ];

        $response = $this->post(route('survey.store', $this->inactiveSurvey), $responseData);
        $response->assertStatus(403);

        // Reactivate the survey
        $this->inactiveSurvey->update(['is_active' => true]);

        // Now it should accept responses
        $response = $this->post(route('survey.store', $this->inactiveSurvey), $responseData);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        // Verify response was stored
        $this->assertDatabaseHas('responses', [
            'survey_id' => $this->inactiveSurvey->id,
            'nps_score' => 8,
            'open_text' => 'Test response'
        ]);
    }

    public function test_inactive_survey_can_still_be_viewed_by_admin()
    {
        // Admin should still be able to view inactive surveys in the admin panel
        $response = $this->get(route('admin.surveys.show', $this->inactiveSurvey));

        $response->assertStatus(200);
        $response->assertSee('Inactive Survey');
    }

    public function test_inactive_survey_can_still_be_edited_by_admin()
    {
        // Admin should still be able to edit inactive surveys
        $response = $this->get(route('admin.surveys.edit', $this->inactiveSurvey));

        $response->assertStatus(200);
        $response->assertSee('Edit Survey');
        $response->assertSee('Inactive Survey');
    }

    public function test_survey_status_affects_public_access_only()
    {
        // Public access should be blocked
        $publicResponse = $this->get(route('survey.show', $this->inactiveSurvey));
        $publicResponse->assertStatus(404);

        // Admin access should work
        $adminResponse = $this->get(route('admin.surveys.show', $this->inactiveSurvey));
        $adminResponse->assertStatus(200);

        // Admin edit should work
        $editResponse = $this->get(route('admin.surveys.edit', $this->inactiveSurvey));
        $editResponse->assertStatus(200);
    }

    public function test_survey_list_shows_both_active_and_inactive_surveys_to_admin()
    {
        $response = $this->get(route('admin.surveys.index'));

        $response->assertStatus(200);
        $response->assertSee('Active Survey');
        $response->assertSee('Inactive Survey');
    }

    public function test_inactive_survey_responses_endpoint_protection()
    {
        // Create some responses for the inactive survey (from when it was active)
        Response::create([
            'survey_id' => $this->inactiveSurvey->id,
            'session_id' => 'test-session-1',
            'nps_score' => 8,
            'completed_at' => now()
        ]);

        // Admin should still be able to view responses for inactive surveys
        $response = $this->get(route('admin.surveys.responses', $this->inactiveSurvey));

        $response->assertStatus(200);
        // Should show the responses even though survey is inactive
    }
}
