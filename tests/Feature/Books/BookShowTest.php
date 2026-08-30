<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_ユーザーは指定した書籍の詳細情報を取得できる(): void
    {
        $reviewer = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $book = Book::factory()->create([
            'title' => 'テストタイトル',
        ]);

        Review::factory()
            ->for($reviewer)
            ->for($book)
            ->create([
                'comment' => 'テストレビュー',
            ]);

        $genre = Genre::factory()->create([
            'name' => 'テストジャンル',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertSee('テストタイトル');
        $response->assertSee('テストジャンル');
        $response->assertSee('テストレビュー');
        $response->assertSee('テストユーザー');
    }

    public function test_認証済みユーザーは書籍詳細画面を表示できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('books.show', $book));

        $response->assertOk();
    }
}
