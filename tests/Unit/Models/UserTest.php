<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_ユーザーの1対多リレーションが正しく定義されている(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()
            ->for($user)
            ->create();
        $review = Review::factory()
            ->for($user)
            ->for($book)
            ->create();
        $favorite = Favorite::factory()
            ->for($user)
            ->for($book)
            ->create();
        $reviewLike = ReviewLike::factory()
            ->for($user)
            ->for($review)
            ->create();

        $this->assertTrue($user->books->contains($book));
        $this->assertTrue($user->reviews->contains($review));
        $this->assertTrue($user->favorites->contains($favorite));
        $this->assertTrue($user->reviewLikes->contains($reviewLike));
    }

    public function test_ユーザーの多対多リレーションが正しく定義されている(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create();

        $user->favoriteBooks()->attach($book);
        $user->likedReviews()->attach($review);

        $this->assertTrue($user->favoriteBooks->contains($book));
        $this->assertTrue($user->likedReviews->contains($review));
    }
}
