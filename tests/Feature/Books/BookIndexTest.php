<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
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

    public function test_タイトルに部分一致するキーワードで検索できる(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        Book::factory()->create([
            'title' => 'Laravel実践ガイド',
        ]);

        Book::factory()->create([
            'title' => 'PHP基礎',
        ]);

        $response = $this->get('/books?keyword=Laravel');

        $response->assertOk();
        $response->assertSee('Laravel入門');
        $response->assertSee('Laravel実践ガイド');
        $response->assertDontSee('PHP基礎');
    }

    public function test_著者名に部分一致するキーワードで検索できる(): void
    {
        Book::factory()->create([
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'author' => '山田花子',
        ]);

        Book::factory()->create([
            'author' => '佐藤一郎',
        ]);

        $response = $this->get('/books?keyword=山田');

        $response->assertOk();
        $response->assertSee('山田太郎');
        $response->assertSee('山田花子');
        $response->assertDontSee('佐藤一郎');
    }

    public function test_該当しないキーワードで検索する(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        Book::factory()->create([
            'title' => 'Laravel実践ガイド',
        ]);

        Book::factory()->create([
            'title' => 'PHP基礎',
        ]);

        $response = $this->get('/books?keyword=HTML');

        $response->assertOk();

        $response->assertViewHas('books', function ($books) {
            return $books->isEmpty();
        });

        $response->assertDontSee('Laravel入門');
        $response->assertDontSee('Laravel実践ガイド');
        $response->assertDontSee('PHP基礎');
    }

    public function test_ジャンルで書籍を絞り込める(): void
    {
        $technicalGenre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $novelGenre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $laravelBook = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $novelBook = Book::factory()->create([
            'title' => '星空の物語',
        ]);

        $laravelBook->genres()->attach($technicalGenre);
        $novelBook->genres()->attach($novelGenre);

        $response = $this->get("/books?genre={$technicalGenre->id}");

        $response->assertOk();
        $response->assertSee('Laravel入門');
        $response->assertDontSee('星空の物語');
    }

    public function test_キーワードとジャンルを組み合わせて検索する(): void
    {
        $technicalGenre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $novelGenre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $laravelBook = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $phpBook = Book::factory()->create([
            'title' => 'PHP基礎',
        ]);

        $novelBook = Book::factory()->create([
            'title' => '星空の物語',
        ]);

        $laravelBook->genres()->attach($technicalGenre);
        $phpBook->genres()->attach($technicalGenre);
        $novelBook->genres()->attach($novelGenre);

        $response = $this->get("/books?keyword=Laravel&genre={$technicalGenre->id}");

        $response->assertOk();
        $response->assertSee('Laravel入門');
        $response->assertDontSee('PHP基礎');
        $response->assertDontSee('星空の物語');
    }

    public function test_検索条件を維持したまま指定したページに遷移できる(): void
    {
        $genre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $books = Book::factory()->count(11)->create([
            'title' => 'Laravel入門',
        ]);

        $books->each(function ($book) use ($genre) {
            $book->genres()->attach($genre);
        });

        $response = $this->get("/books?keyword=Laravel&genre={$genre->id}");

        $response->assertOk();

        $response->assertViewHas('books', function ($books) use ($genre) {
            $nextPageUrl = $books->nextPageUrl();

            return str_contains($nextPageUrl, 'keyword=Laravel')
                && str_contains($nextPageUrl, "genre={$genre->id}")
                && str_contains($nextPageUrl, 'page=2');
        });

    }

    public function test_並び順を「新しい順」に指定する(): void
    {
        Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => now()->subDays(2),
        ]);

        Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => now(),
        ]);

        $response = $this->get('/books?sort=newest');

        $response->assertOk();
        $response->assertSeeInOrder([
            '新しい書籍',
            '古い書籍',
        ]);
    }

    public function test_並び順を「古い順」に指定する(): void
    {
        Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => now()->subDays(2),
        ]);

        Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => now(),
        ]);

        $response = $this->get('/books?sort=oldest');

        $response->assertOk();
        $response->assertSeeInOrder([
            '古い書籍',
            '新しい書籍',
        ]);
    }

    public function test_並び順を「タイトル昇順」に指定する(): void
    {
        Book::factory()->create([
            'title' => 'Apple',
        ]);

        Book::factory()->create([
            'title' => 'Banana',
        ]);

        Book::factory()->create([
            'title' => 'Cherry',
        ]);

        $response = $this->get('/books?sort=title');

        $response->assertOk();
        $response->assertSeeInOrder([
            'Apple',
            'Banana',
            'Cherry',
        ]);
    }

    public function test_並び順を「評価が高い順」に指定する(): void
    {
        $fiveStarBook = Book::factory()->create([
            'title' => '評価5の書籍',
        ]);

        $fourStarBook = Book::factory()->create([
            'title' => '評価4の書籍',
        ]);

        $threeStarBook = Book::factory()->create([
            'title' => '評価3の書籍',
        ]);

        Review::factory()->for($fiveStarBook)->create([
            'rating' => 5,
        ]);

        Review::factory()->for($fourStarBook)->create([
            'rating' => 4,
        ]);

        Review::factory()->for($threeStarBook)->create([
            'rating' => 3,
        ]);

        $response = $this->get('/books?sort=rating');

        $response->assertOk();
        $response->assertSeeInOrder([
            '評価5の書籍',
            '評価4の書籍',
            '評価3の書籍',
        ]);
    }

    public function test_「評価が高い順」に指定した場合_レビューがない書籍は最後に表示される(): void
    {
        $ratedBook = Book::factory()->create([
            'title' => '評価ありの書籍',
        ]);

        Book::factory()->create([
            'title' => '評価がない書籍',
        ]);

        Review::factory()->for($ratedBook)->create([
            'rating' => 5,
        ]);

        $response = $this->get('/books?sort=rating');

        $response->assertOk();
        $response->assertSeeInOrder([
            '評価ありの書籍',
            '評価がない書籍',
        ]);
    }

    public function test_並び順を指定せず書籍一覧を表示する(): void
    {
        Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => now()->subDays(2),
        ]);

        Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => now(),
        ]);

        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            '新しい書籍',
            '古い書籍',
        ]);
    }

    public function test_不正な並び順を指定した場合は新しい順に表示される(): void
    {
        Book::factory()->create([
            'title' => '古い書籍',
            'created_at' => now()->subDays(2),
        ]);

        Book::factory()->create([
            'title' => '新しい書籍',
            'created_at' => now(),
        ]);

        $response = $this->get('/books?sort=invalid');

        $response->assertOk();
        $response->assertSeeInOrder([
            '新しい書籍',
            '古い書籍',
        ]);
    }
}
