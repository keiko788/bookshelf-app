<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_未認証ユーザーがレビューいいねトグルを押下するとログイン画面へリダイレクトされる(): void
    {
        $review = Review::factory()->create();

        $response = $this->post(route('reviews.like', $review));

        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーはレビューにいいねすると_いいねが登録され件数が1増える(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()
            ->for($book)
            ->create();

        $beforeCount = $review->reviewLikes()->count();

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.like', $review));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $afterCount = $review->reviewLikes()->count();

        $this->assertSame(
            $beforeCount + 1,
            $afterCount
        );
    }

    public function test_いいね済みレビューを再度押下すると_いいねが解除され件数が1減る(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()
            ->for($book)
            ->create();

        ReviewLike::factory()
            ->for($user)
            ->for($review)
            ->create();

        $beforeCount = $review->reviewLikes()->count();

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('reviews.like', $review));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $afterCount = $review->reviewLikes()->count();

        $this->assertSame(
            $beforeCount - 1,
            $afterCount
        );
    }
}
