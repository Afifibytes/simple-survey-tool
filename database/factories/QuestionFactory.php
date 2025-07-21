<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\Survey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Question::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'type' => $this->faker->randomElement(['nps', 'text']),
            'text' => $this->faker->sentence() . '?',
            'order' => $this->faker->numberBetween(0, 10),
            'options' => null,
        ];
    }

    /**
     * Indicate that the question is an NPS question.
     */
    public function nps(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'nps',
            'text' => 'How likely are you to recommend us to a friend or colleague?',
        ]);
    }

    /**
     * Indicate that the question is a text question.
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'text',
            'text' => 'What can we do to improve your experience?',
        ]);
    }

    /**
     * Indicate that the question is an AI follow-up question.
     */
    public function aiFollowUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'ai_follow_up',
            'text' => 'Can you tell us more about that?',
        ]);
    }
}
