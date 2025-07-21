<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_surveys_index()
    {
        $response = $this->get(route('admin.surveys.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_survey()
    {
        $surveyData = [
            'name' => 'Test Survey',
            'description' => 'A test survey',
            'questions' => [
                [
                    'type' => 'nps',
                    'text' => 'How likely are you to recommend us?',
                    'options' => null
                ],
                [
                    'type' => 'text',
                    'text' => 'What can we improve?',
                    'options' => null
                ]
            ]
        ];

        $response = $this->post(route('admin.surveys.store'), $surveyData);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('surveys', [
            'name' => 'Test Survey',
            'description' => 'A test survey'
        ]);
        $this->assertDatabaseCount('questions', 2);
    }

    public function test_can_view_survey()
    {
        $survey = Survey::factory()->create();
        Question::factory()->create(['survey_id' => $survey->id, 'type' => 'nps']);
        Question::factory()->create(['survey_id' => $survey->id, 'type' => 'text']);

        $response = $this->get(route('admin.surveys.show', $survey));
        $response->assertStatus(200);
    }
}
