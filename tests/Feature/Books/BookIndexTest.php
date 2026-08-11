<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_ユーザーは書籍一覧を取得できる(): void
    {
        $books = Book::factory()->count(3)->create();

        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertViewHas('books');

        foreach ($books as $book) {
            $response->assertSee($book->title);
        }
    }

    public function test_ユーザーは書籍一覧を10件ごとのページネーションで表示できる(): void
    {
        Book::factory()->count(11)->create();

        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertViewHas(
            'books',
            fn ($viewBooks) => $viewBooks->total() === 11
                && $viewBooks->perPage() === 10
                && $viewBooks->lastPage() === 2
        );
    }

    public function test_書籍一覧画面に書籍のジャンルを表示できる(): void
    {
        $book = Book::factory()->create();
        $genre1 = Genre::factory()->create();
        $genre2 = Genre::factory()->create();

        $book->genres()->attach([$genre1->id, $genre2->id]);

        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertSee($genre1->name);
        $response->assertSee($genre2->name);
    }
}
