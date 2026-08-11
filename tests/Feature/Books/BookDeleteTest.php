<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍の削除は書籍の登録者だけが許可される(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $this->assertTrue($owner->can('delete', $book));
        $this->assertFalse($other->can('delete', $book));
    }

    public function test_書籍の登録者は書籍を削除できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_書籍の登録者以外は書籍を削除できない(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($other)
            ->delete(route('books.destroy', $book));

        $response->assertForbidden();

        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    public function test_書籍を削除すると紐づくレビューとレビューいいねも削除される(): void
    {
        $user = User::factory()->create();
        $reviewer = User::factory()->create();
        $book = Book::factory()->for($user)->create();
        $review = Review::factory()
            ->for($book)
            ->for($reviewer)
            ->create();
        $reviewLike = ReviewLike::factory()
            ->for($user)
            ->for($review)
            ->create();

        $response = $this->actingAs($user)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
        $this->assertDatabaseMissing('review_likes', [
            'id' => $reviewLike->id,
        ]);
    }

    public function test_書籍を削除すると紐づくお気に入りも削除される(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();
        $favorite = Favorite::factory()
            ->for($user)
            ->for($book)
            ->create();

        $response = $this->actingAs($user)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
        $this->assertDatabaseMissing('favorites', [
            'id' => $favorite->id,
        ]);
    }

    public function test_書籍を削除すると書籍とジャンルの紐付けが解除される(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();
        $genre = Genre::factory()->create();
        $book->genres()->attach($genre);

        $response = $this->actingAs($user)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }
}
