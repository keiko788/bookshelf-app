<?php

namespace Tests\Unit\Models;

use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_レビューいいねのリレーションが定義されている(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()
            ->for($user)
            ->create();
        $reviewLike = ReviewLike::factory()
            ->for($user)
            ->for($review)
            ->create();

        $this->assertTrue($reviewLike->user->is($user));
        $this->assertTrue($reviewLike->review->is($review));
    }
}
