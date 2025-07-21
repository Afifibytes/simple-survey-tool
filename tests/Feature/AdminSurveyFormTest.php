<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSurveyFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_survey_successfully()
    {
        $surveyData = [
            'name' => 'New Customer Survey',
            'description' => 'A survey to understand customer needs',
            'is_active' => true,
            'questions' => [
                [
                    'type' => 'nps',
                    'text' => 'How likely are you to recommend our service?',
                    'options' => null
                ],
                [
                    'type' => 'text',
                    'text' => 'What improvements would you suggest?',
                    'options' => null
                ]
            ]
        ];

        $response = $this->post(route('admin.surveys.store'), $surveyData);

        // Should redirect successfully (not show validation error)
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Verify survey was created
        $this->assertDatabaseHas('surveys', [
            'name' => 'New Customer Survey',
            'description' => 'A survey to understand customer needs',
            'is_active' => true
        ]);

        // Verify questions were created
        $survey = Survey::where('name', 'New Customer Survey')->first();
        $this->assertEquals(2, $survey->questions()->count());

        $npsQuestion = $survey->questions()->where('type', 'nps')->first();
        $textQuestion = $survey->questions()->where('type', 'text')->first();

        $this->assertEquals('How likely are you to recommend our service?', $npsQuestion->text);
        $this->assertEquals('What improvements would you suggest?', $textQuestion->text);
    }

    public function test_admin_can_update_survey_successfully()
    {
        // Create initial survey
        $survey = Survey::create([
            'name' => 'Original Survey Name',
            'description' => 'Original description',
            'is_active' => true
        ]);

        $survey->questions()->create([
            'type' => 'nps',
            'text' => 'Original NPS question?',
            'order' => 0
        ]);

        $survey->questions()->create([
            'type' => 'text',
            'text' => 'Original text question?',
            'order' => 1
        ]);

        // Update survey data (note: is_active checkbox won't be sent if unchecked)
        $updateData = [
            'name' => 'Updated Survey Name',
            'description' => 'Updated description',
            // is_active not included = false (unchecked checkbox)
            'questions' => [
                [
                    'type' => 'nps',
                    'text' => 'Updated NPS question - how satisfied are you?',
                    'options' => null
                ],
                [
                    'type' => 'text',
                    'text' => 'Updated text question - any feedback?',
                    'options' => null
                ]
            ]
        ];

        $response = $this->put(route('admin.surveys.update', $survey), $updateData);

        // Should redirect successfully (not show validation error)
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Verify survey was updated
        $survey->refresh();
        $this->assertEquals('Updated Survey Name', $survey->name);
        $this->assertEquals('Updated description', $survey->description);
        // Note: is_active should remain true since checkbox wasn't sent (unchecked checkboxes don't send data)
        $this->assertTrue($survey->is_active);

        // Verify questions were updated
        $this->assertEquals(2, $survey->questions()->count());

        $npsQuestion = $survey->questions()->where('type', 'nps')->first();
        $textQuestion = $survey->questions()->where('type', 'text')->first();

        $this->assertEquals('Updated NPS question - how satisfied are you?', $npsQuestion->text);
        $this->assertEquals('Updated text question - any feedback?', $textQuestion->text);
    }

    public function test_admin_survey_validation_works_correctly()
    {
        // Test missing survey name
        $invalidData = [
            'name' => '', // Empty name should fail
            'description' => 'Valid description',
            'questions' => [
                [
                    'type' => 'nps',
                    'text' => 'Valid NPS question?',
                    'options' => null
                ],
                [
                    'type' => 'text',
                    'text' => 'Valid text question?',
                    'options' => null
                ]
            ]
        ];

        $response = $this->post(route('admin.surveys.store'), $invalidData);

        // Should return validation errors
        $response->assertSessionHasErrors(['name']);
    }

    public function test_admin_survey_requires_exactly_two_questions()
    {
        // Test with only one question
        $invalidData = [
            'name' => 'Valid Survey Name',
            'description' => 'Valid description',
            'questions' => [
                [
                    'type' => 'nps',
                    'text' => 'Only one question?',
                    'options' => null
                ]
            ]
        ];

        $response = $this->post(route('admin.surveys.store'), $invalidData);

        // Should return validation errors for questions count
        $response->assertSessionHasErrors(['questions']);
    }

    public function test_admin_survey_question_types_are_validated()
    {
        // Test with invalid question type
        $invalidData = [
            'name' => 'Valid Survey Name',
            'description' => 'Valid description',
            'questions' => [
                [
                    'type' => 'invalid_type', // Invalid type
                    'text' => 'Valid question text?',
                    'options' => null
                ],
                [
                    'type' => 'text',
                    'text' => 'Valid text question?',
                    'options' => null
                ]
            ]
        ];

        $response = $this->post(route('admin.surveys.store'), $invalidData);

        // Should return validation errors for question type
        $response->assertSessionHasErrors(['questions.0.type']);
    }

    public function test_admin_can_view_survey_edit_form()
    {
        // Create a survey to edit
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

        $response = $this->get(route('admin.surveys.edit', $survey));

        $response->assertStatus(200);
        $response->assertSee('Edit Survey');
        $response->assertSee($survey->name);
        $response->assertSee($survey->description);
        $response->assertSee('NPS Question?');
        $response->assertSee('Text Question?');

        // Verify the form has the correct ID (not the conflicting one)
        $response->assertSee('id="admin-survey-form"', false);
        $response->assertDontSee('id="survey-form"', false);
    }

    public function test_admin_can_view_survey_create_form()
    {
        $response = $this->get(route('admin.surveys.create'));

        $response->assertStatus(200);
        $response->assertSee('Create Survey');

        // Verify the form has the correct ID (not the conflicting one)
        $response->assertSee('id="admin-survey-form"', false);
        $response->assertDontSee('id="survey-form"', false);
    }

    public function test_survey_update_does_not_trigger_response_validation()
    {
        // Create a survey
        $survey = Survey::create([
            'name' => 'Test Survey',
            'description' => 'Test description'
        ]);

        $survey->questions()->create([
            'type' => 'nps',
            'text' => 'Original NPS?',
            'order' => 0
        ]);

        $survey->questions()->create([
            'type' => 'text',
            'text' => 'Original text?',
            'order' => 1
        ]);

        // Update with valid survey data (no NPS score or open text needed)
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
                    'text' => 'Updated text question?',
                    'options' => null
                ]
            ]
        ];

        $response = $this->put(route('admin.surveys.update', $survey), $updateData);

        // Should NOT get the response validation error
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Specifically check that we don't get the response validation message
        $response->assertSessionMissing('errors');

        // Verify the update was successful
        $survey->refresh();
        $this->assertEquals('Updated Survey', $survey->name);
    }
}
