<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_レビューの1対多および多対1リレーションが正しく定義されている(): void
    {
        $reviewer = User::factory()->create();
        $likedUser = User::factory()->create();

        $book = Book::factory()
            ->for($reviewer)
            ->create();

        $review = Review::factory()
            ->for($reviewer)
            ->for($book)
            ->create();

        $reviewLike = ReviewLike::factory()
            ->for($likedUser)
            ->for($review)
            ->create();

        $this->assertTrue($review->user->is($reviewer));
        $this->assertTrue($review->book->is($book));
        $this->assertTrue($review->reviewLikes->contains($reviewLike));
    }

    public function test_レビューの多対多リレーションが正しく定義されている(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $review->likedByUsers()->attach($user);

        $this->assertTrue($review->likedByUsers->contains($user));
    }
}
