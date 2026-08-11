<?php

namespace Tests\Feature\Reviews;

use App\Models\Book;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_削除はレビュー投稿者だけが許可される(): void
    {
        $reviewer = User::factory()->create();
        $other = User::factory()->create();
        $review = Review::factory()
            ->for($reviewer)
            ->create();

        $this->assertTrue($reviewer->can('delete', $review));
        $this->assertFalse($other->can('delete', $review));
    }

    public function test_レビュー投稿者本人がレビューを削除する(): void
    {
        $reviewer = User::factory()->create();
        $book = Book::factory()->create();
        $review = Review::factory()
            ->for($reviewer)
            ->for($book)
            ->create();

        $response = $this->actingAs($reviewer)
            ->delete(route('reviews.destroy', $review));

        $response->assertRedirect(route('books.show', $book));
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_レビュー投稿者以外はレビューを削除できない(): void
    {
        $reviewer = User::factory()->create();
        $other = User::factory()->create();
        $review = Review::factory()
            ->for($reviewer)
            ->create();

        $response = $this->actingAs($other)->delete(route('reviews.destroy', $review));

        $response->assertForbidden();
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_レビューを削除するとレビューに紐づくいいねも削除される(): void
    {
        $reviewer = User::factory()->create();
        $liker = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()
            ->for($reviewer)
            ->for($book)
            ->create();

        $reviewLike = ReviewLike::factory()
            ->for($liker)
            ->for($review)
            ->create();

        $response = $this->actingAs($reviewer)->delete(route('reviews.destroy', $review));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('review_likes', [
            'id' => $reviewLike->id,
        ]);
    }
}
