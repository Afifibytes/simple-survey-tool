<?php

namespace Database\Factories;

use App\Models\Survey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Survey>
 */
class SurveyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Survey::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the survey is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a survey with questions.
     */
    public function withQuestions(): static
    {
        return $this->afterCreating(function (Survey $survey) {
            $survey->questions()->create([
                'type' => 'nps',
                'text' => 'How likely are you to recommend us?',
                'order' => 0,
            ]);

            $survey->questions()->create([
                'type' => 'text',
                'text' => 'What can we do to improve?',
                'order' => 1,
            ]);
        });
    }
}
