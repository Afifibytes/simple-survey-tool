<?php

namespace Database\Seeders;

use App\Models\Survey;
use App\Models\Question;
use App\Models\Response;
use Illuminate\Database\Seeder;

class SurveySeeder extends Seeder
{
    public function run(): void
    {
        // Create a sample survey
        $survey = Survey::create([
            'name' => 'Customer Satisfaction Survey',
            'description' => 'Help us improve our service by sharing your feedback.',
            'is_active' => true,
        ]);

        // Create NPS question
        Question::create([
            'survey_id' => $survey->id,
            'type' => 'nps',
            'text' => 'How likely are you to recommend our service to a friend or colleague?',
            'order' => 0,
        ]);

        // Create open text question
        Question::create([
            'survey_id' => $survey->id,
            'type' => 'text',
            'text' => 'What can we do to improve your experience with our service?',
            'order' => 1,
        ]);

        // Create some sample responses
        $responses = [
            [
                'nps_score' => 9,
                'open_text' => 'Great service overall, very satisfied with the quality.',
                'ai_follow_up_question' => 'What specific aspect of our service quality impressed you the most?',
                'ai_follow_up_answer' => 'The customer support team was incredibly helpful and responsive.',
            ],
            [
                'nps_score' => 7,
                'open_text' => 'Good service but could be faster.',
                'ai_follow_up_question' => 'Which part of our service would you like to see improved for speed?',
                'ai_follow_up_answer' => 'The initial response time could be quicker.',
            ],
            [
                'nps_score' => 5,
                'open_text' => 'Average experience, nothing special.',
                'ai_follow_up_question' => 'What would make your experience more memorable and positive?',
                'ai_follow_up_answer' => 'More personalized attention and follow-up.',
            ],
        ];

        foreach ($responses as $index => $responseData) {
            Response::create([
                'survey_id' => $survey->id,
                'session_id' => 'sample_session_' . ($index + 1),
                'nps_score' => $responseData['nps_score'],
                'open_text' => $responseData['open_text'],
                'ai_follow_up_question' => $responseData['ai_follow_up_question'],
                'ai_follow_up_answer' => $responseData['ai_follow_up_answer'],
                'completed_at' => now()->subDays(rand(1, 7)),
                'created_at' => now()->subDays(rand(1, 7)),
                'updated_at' => now()->subDays(rand(1, 7)),
            ]);
        }

        // Create another survey
        $survey2 = Survey::create([
            'name' => 'Product Feedback Survey',
            'description' => 'Tell us what you think about our latest product.',
            'is_active' => true,
        ]);

        // Create questions for second survey
        Question::create([
            'survey_id' => $survey2->id,
            'type' => 'nps',
            'text' => 'How likely are you to recommend this product to others?',
            'order' => 0,
        ]);

        Question::create([
            'survey_id' => $survey2->id,
            'type' => 'text',
            'text' => 'What features would you like to see added to this product?',
            'order' => 1,
        ]);
    }
}
