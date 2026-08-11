<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookShowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍詳細を正しい_jso_n形式で取得できる(): void
    {
        $book = Book::factory()->create();

        $genre = Genre::factory()->create();

        $book->genres()->attach($genre);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'author',
                'isbn',
                'published_date',
                'description',
                'image_url',
                'genres' => [
                    '*' => [
                        'id',
                        'name',
                    ],
                ],
                'reviews' => [
                    '*' => [
                        'id',
                        'user_name',
                        'rating',
                        'comment',
                        'created_at',
                    ],
                ],
            ],
        ]);
    }

    public function test_書籍詳細の_jso_nレスポンス内容が正しい(): void
    {
        $reviewer = User::factory()->create();

        $book = Book::factory()->create();

        $genre = Genre::factory()->create();

        $book->genres()->attach($genre);

        $review = Review::factory()
            ->for($reviewer)
            ->for($book)->create();

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();

        $response->assertJsonPath('data.id', $book->id);
        $response->assertJsonPath('data.title', $book->title);
        $response->assertJsonPath('data.author', $book->author);
        $response->assertJsonPath('data.isbn', $book->isbn);
        $response->assertJsonPath('data.published_date', $book->published_date);

        $response->assertJsonPath('data.genres.0.id', $genre->id);
        $response->assertJsonPath('data.genres.0.name', $genre->name);

        $response->assertJsonPath('data.reviews.0.id', $review->id);
        $response->assertJsonPath('data.reviews.0.user_name', $reviewer->name);
        $response->assertJsonPath('data.reviews.0.rating', $review->rating);
        $response->assertJsonPath('data.reviews.0.comment', $review->comment);
        $response->assertJsonPath(
            'data.reviews.0.created_at',
            $review->created_at->format('Y-m-d H:i:s'));
    }

    public function test_存在しない書籍_i_dを指定した場合_404エラーが返される(): void
    {
        $book = Book::factory()->create();

        $nonExistentId = $book->id + 999;

        $response = $this->getJson("/api/v1/books/{$nonExistentId}");

        $response->assertNotFound();

        $response->assertExactJson([
            'error' => '書籍が見つかりませんでした。',
        ]);
    }

    public function test_不正な形式の書籍_i_dを指定した場合_404エラーが返される(): void
    {
        $response = $this->getJson('/api/v1/books/abc');

        $response->assertNotFound();

        $response->assertExactJson([
            'error' => '書籍が見つかりませんでした。',
        ]);
    }
}
