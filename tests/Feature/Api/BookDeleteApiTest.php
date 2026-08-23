<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookDeleteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍を削除すると_204_を返す(): void
    {
        $book = Book::factory()->create();

        Sanctum::actingAs($book->user);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_書籍を削除すると_ジャンルとの関連データも削除される(): void
    {
        $book = Book::factory()->create();

        $genre = Genre::factory()->create();

        $book->genres()->attach($genre);

        Sanctum::actingAs($book->user);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_書籍を削除すると_関連するレビューとレビューいいねも削除される(): void
    {
        $book = Book::factory()->create();

        $review = Review::factory()
            ->for($book)
            ->create();

        $reviewLike = ReviewLike::factory()
            ->for($review)
            ->create();

        Sanctum::actingAs($book->user);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'id' => $reviewLike->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_書籍を削除すると_関連するお気に入りも削除される(): void
    {
        $book = Book::factory()->create();

        $favorite = Favorite::factory()
            ->for($book)
            ->create();

        Sanctum::actingAs($book->user);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('favorites', [
            'id' => $favorite->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_存在しない書籍_i_dを削除すると_404が返される(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/books/99999');

        $response->assertNotFound();
        $response->assertExactJson([
            'error' => '書籍が見つかりませんでした。',
        ]);
    }

    public function test_不正な形式の書籍_i_dを削除すると_404が返される(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/books/abc');

        $response->assertNotFound();
        $response->assertExactJson([
            'error' => '書籍が見つかりませんでした。',
        ]);
    }
}
