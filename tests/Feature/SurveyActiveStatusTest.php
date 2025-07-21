<?php

namespace Tests\Feature;

use App\Models\Survey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyActiveStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_survey_can_be_deactivated_via_checkbox()
    {
        // Create an active survey
        $survey = Survey::create([
            'name' => 'Test Survey',
            'description' => 'Test description',
            'is_active' => true
        ]);

        $survey->questions()->create([
            'type' => 'nps',
            'text' => 'NPS Question?',
            'order' => 0
        ]);

        $survey->questions()->create([
            'type' => 'text',
            'text' => 'Text Question?',
            'order' => 1
        ]);

        $this->assertTrue($survey->is_active);

        // Update survey with is_active = 0 (unchecked checkbox)
        $updateData = [
            'name' => 'Updated Survey',
            'description' => 'Updated description',
            'is_active' => '0', // This simulates unchecked checkbox (hidden input sends 0)
            'questions' => [
                [
                    'type' => 'nps',
                    'text' => 'Updated NPS question?',
                    'options' => null
                ],
                [
                    'type' => 'text',
                    'text' => 'Updated text question?',
                    'options' => null
                ]
            ]
        ];

        $response = $this->put(route('admin.surveys.update', $survey), $updateData);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Verify survey was deactivated
        $survey->refresh();
        $this->assertEquals('Updated Survey', $survey->name);
        $this->assertEquals('Updated description', $survey->description);
        $this->assertFalse($survey->is_active); // Should now be false
    }

    public function test_survey_can_be_activated_via_checkbox()
    {
        // Create an inactive survey
        $survey = Survey::create([
            'name' => 'Test Survey',
            'description' => 'Test description',
            'is_active' => false
        ]);

        $survey->questions()->create([
            'type' => 'nps',
            'text' => 'NPS Question?',
            'order' => 0
        ]);

        $survey->questions()->create([
            'type' => 'text',
            'text' => 'Text Question?',
            'order' => 1
        ]);

        $this->assertFalse($survey->is_active);

        // Update survey with is_active = 1 (checked checkbox)
        $updateData = [
            'name' => 'Updated Survey',
            'description' => 'Updated description',
            'is_active' => '1', // This simulates checked checkbox
            'questions' => [
                [
                    'type' => 'nps',
                    'text' => 'Updated NPS question?',
                    'options' => null
                ],
                [
                    'type' => 'text',
                    'text' => 'Updated text question?',
                    'options' => null
                ]
            ]
        ];

        $response = $this->put(route('admin.surveys.update', $survey), $updateData);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Verify survey was activated
        $survey->refresh();
        $this->assertEquals('Updated Survey', $survey->name);
        $this->assertEquals('Updated description', $survey->description);
        $this->assertTrue($survey->is_active); // Should now be true
    }

    public function test_survey_checkbox_behavior_with_hidden_input()
    {
        // Create a survey
        $survey = Survey::create([
            'name' => 'Test Survey',
            'description' => 'Test description',
            'is_active' => true
        ]);

        $survey->questions()->create([
            'type' => 'nps',
            'text' => 'NPS Question?',
            'order' => 0
        ]);

        $survey->questions()->create([
            'type' => 'text',
            'text' => 'Text Question?',
            'order' => 1
        ]);

        // Test that when checkbox is unchecked, hidden input sends 0
        // This simulates the browser behavior where:
        // - Hidden input sends: is_active=0
        // - Checkbox (when unchecked) sends: nothing
        // - Result: is_active=0 (from hidden input)

        $updateData = [
            'name' => 'Test Survey',
            'description' => 'Test description',
            'is_active' => '0', // Only hidden input value (checkbox unchecked)
            'questions' => [
                [
                    'type' => 'nps',
                    'text' => 'NPS Question?',
                    'options' => null
                ],
                [
                    'type' => 'text',
                    'text' => 'Text Question?',
                    'options' => null
                ]
            ]
        ];

        $response = $this->put(route('admin.surveys.update', $survey), $updateData);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Verify survey was deactivated
        $survey->refresh();
        $this->assertFalse($survey->is_active);
    }

    public function test_survey_checkbox_behavior_when_checked()
    {
        // Create an inactive survey
        $survey = Survey::create([
            'name' => 'Test Survey',
            'description' => 'Test description',
            'is_active' => false
        ]);

        $survey->questions()->create([
            'type' => 'nps',
            'text' => 'NPS Question?',
            'order' => 0
        ]);

        $survey->questions()->create([
            'type' => 'text',
            'text' => 'Text Question?',
            'order' => 1
        ]);

        // Test that when checkbox is checked, it overrides hidden input
        // This simulates the browser behavior where:
        // - Hidden input sends: is_active=0
        // - Checkbox (when checked) sends: is_active=1
        // - Result: is_active=1 (checkbox value overrides hidden input)

        $updateData = [
            'name' => 'Test Survey',
            'description' => 'Test description',
            'is_active' => '1', // Checkbox checked value overrides hidden input
            'questions' => [
                [
                    'type' => 'nps',
                    'text' => 'NPS Question?',
                    'options' => null
                ],
                [
                    'type' => 'text',
                    'text' => 'Text Question?',
                    'options' => null
                ]
            ]
        ];

        $response = $this->put(route('admin.surveys.update', $survey), $updateData);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Verify survey was activated
        $survey->refresh();
        $this->assertTrue($survey->is_active);
    }

    public function test_survey_edit_form_shows_correct_checkbox_state()
    {
        // Test active survey
        $activeSurvey = Survey::create([
            'name' => 'Active Survey',
            'description' => 'This survey is active',
            'is_active' => true
        ]);

        $activeSurvey->questions()->create(['type' => 'nps', 'text' => 'NPS?', 'order' => 0]);
        $activeSurvey->questions()->create(['type' => 'text', 'text' => 'Text?', 'order' => 1]);

        $response = $this->get(route('admin.surveys.edit', $activeSurvey));
        $response->assertStatus(200);
        $response->assertSee('checked', false); // Checkbox should be checked

        // Test inactive survey
        $inactiveSurvey = Survey::create([
            'name' => 'Inactive Survey',
            'description' => 'This survey is inactive',
            'is_active' => false
        ]);

        $inactiveSurvey->questions()->create(['type' => 'nps', 'text' => 'NPS?', 'order' => 0]);
        $inactiveSurvey->questions()->create(['type' => 'text', 'text' => 'Text?', 'order' => 1]);

        $response = $this->get(route('admin.surveys.edit', $inactiveSurvey));
        $response->assertStatus(200);
        // For inactive survey, the checkbox input should exist but not be checked
        $response->assertSee('name="is_active"', false);
        $response->assertSee('Survey is active and accepting responses');
    }
}
