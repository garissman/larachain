<?php

namespace Garissman\LaraChain\Database\Factories;

use Garissman\LaraChain\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Tag::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
        ];
    }

    /**
     * Associate the tag with a taggable model.
     */
    public function taggable($modelType, $modelId)
    {
        return $this->state(function (array $attributes) use ($modelType, $modelId) {
            return [
                'taggable_type' => $modelType,
                'taggable_id' => $modelId,
            ];
        });
    }
}
