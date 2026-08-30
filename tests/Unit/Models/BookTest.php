<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍の1対多および多対1リレーションが正しく定義されている(): void
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
        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->for($book)
            ->create();

        $this->assertTrue($book->user->is($user));
        $this->assertTrue($book->reviews->contains($review));
        $this->assertTrue($book->favorites->contains($favorite));
        $this->assertTrue($book->readingPlans->contains($readingPlan));
    }

    public function test_書籍の多対多リレーションが正しく定義されている(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $genre = Genre::factory()->create();

        $book->genres()->attach($genre);
        $book->favoriteUsers()->attach($user);

        $this->assertTrue($book->genres->contains($genre));
        $this->assertTrue($book->favoriteUsers->contains($user));
    }
}
