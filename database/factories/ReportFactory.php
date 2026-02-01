<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use App\Models\Ad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reasons = [
            'fraud' => 'This appears to be fraudulent',
            'spam' => 'This is spam content',
            'inappropriate' => 'Contains inappropriate content',
            'misleading' => 'The information is misleading',
            'scam' => 'This appears to be a scam',
            'offensive' => 'Contains offensive material',
            'duplicate' => 'This is a duplicate listing',
            'counterfeit' => 'Selling counterfeit products',
        ];

        $reasonKey = fake()->randomElement(array_keys($reasons));

        return [
            'reported_by_user_id' => User::factory(),
            'target_type' => 'ad',
            'target_id' => Ad::factory(),
            'reason' => $reasonKey,
            'title' => 'Report: ' . ucfirst($reasonKey),
            'details' => $reasons[$reasonKey] . '. ' . fake()->sentence(),
            'status' => Report::STATUS_OPEN,
            'assigned_to' => null,
        ];
    }

    /**
     * Indicate the report targets an ad
     */
    public function targetingAd(Ad $ad = null): static
    {
        return $this->state(fn (array $attributes) => [
            'target_type' => 'ad',
            'target_id' => $ad ? $ad->id : Ad::factory(),
        ]);
    }

    /**
     * Indicate the report targets a user
     */
    public function targetingUser(User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'target_type' => 'user',
            'target_id' => $user ? $user->id : User::factory(),
        ]);
    }

    /**
     * Indicate the report is from a specific reporter
     */
    public function by(User $reporter): static
    {
        return $this->state(fn (array $attributes) => [
            'reported_by_user_id' => $reporter->id,
        ]);
    }

    /**
     * Indicate the report is open (default)
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Report::STATUS_OPEN,
            'assigned_to' => null,
        ]);
    }

    /**
     * Indicate the report is under review
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Report::STATUS_UNDER_REVIEW,
        ]);
    }

    /**
     * Indicate the report is resolved
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Report::STATUS_RESOLVED,
        ]);
    }

    /**
     * Indicate the report is closed
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Report::STATUS_CLOSED,
        ]);
    }

    /**
     * Indicate the report is assigned to a moderator
     */
    public function assignedTo(User $moderator): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $moderator->id,
            'status' => $attributes['status'] ?? Report::STATUS_UNDER_REVIEW,
        ]);
    }

    /**
     * Create report with specific reason
     */
    public function withReason(string $reason): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => $reason,
            'title' => 'Report: ' . ucfirst($reason),
        ]);
    }
}
