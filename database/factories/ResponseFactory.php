<?php

namespace Database\Factories;

use App\Models\Response;
use App\Models\Survey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Response>
 */
class ResponseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Response::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'session_id' => $this->faker->uuid(),
            'nps_score' => $this->faker->numberBetween(0, 10),
            'open_text' => $this->faker->paragraph(),
            'ai_follow_up_question' => null,
            'ai_follow_up_answer' => null,
            'completed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the response is incomplete.
     */
    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => null,
        ]);
    }

    /**
     * Indicate that the response has an AI follow-up.
     */
    public function withAiFollowUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_follow_up_question' => $this->faker->sentence() . '?',
            'ai_follow_up_answer' => $this->faker->paragraph(),
        ]);
    }

    /**
     * Indicate that the response is NPS only.
     */
    public function npsOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'open_text' => null,
            'ai_follow_up_question' => null,
            'ai_follow_up_answer' => null,
        ]);
    }

    /**
     * Indicate that the response is text only.
     */
    public function textOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'nps_score' => null,
        ]);
    }
}
