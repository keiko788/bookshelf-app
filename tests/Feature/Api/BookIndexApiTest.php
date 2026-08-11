<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookIndexApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍一覧を正しい_jso_n形式で取得できる(): void
    {
        Book::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
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
                    'average_rating',
                    'reviews_count',
                ],
            ],
            'meta' => [
                'current_page',
                'per_page',
                'total',
                'last_page',
            ],
        ]);
    }

    public function test_書籍一覧の_jso_nレスポンス内容が正しい(): void
    {
        $book = Book::factory()->create([
            'title' => 'APIテスト書籍',
            'author' => 'APIテスト著者',
            'isbn' => '9780123456789',
            'published_date' => '2026-08-01',
        ]);

        $genre = Genre::factory()->create([
            'name' => 'テストジャンル',
        ]);

        $book->genres()->attach($genre);

        Review::factory()->for($book)->create([
            'rating' => 5,
        ]);

        Review::factory()->for($book)->create([
            'rating' => 4,
        ]);

        $response = $this->getJson('/api/v1/books');

        $response->assertOk();

        $response->assertJsonPath('data.0.title', 'APIテスト書籍');
        $response->assertJsonPath('data.0.author', 'APIテスト著者');
        $response->assertJsonPath('data.0.isbn', '9780123456789');
        $response->assertJsonPath('data.0.published_date', '2026-08-01');

        $response->assertJsonPath('data.0.genres.0.id', $genre->id);
        $response->assertJsonPath('data.0.genres.0.name', 'テストジャンル');

        $response->assertJsonPath('data.0.average_rating', 4.5);
        $response->assertJsonPath('data.0.reviews_count', 2);
    }

    public function test_キーワードで書籍を検索できる(): void
    {
        $book1 = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $book2 = Book::factory()->create([
            'title' => 'Laravel実践ガイド',
        ]);

        $book3 = Book::factory()->create([
            'title' => 'PHP基礎',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=Laravel');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');

        $response->assertJsonFragment([
            'title' => $book1->title,
        ]);

        $response->assertJsonFragment([
            'title' => $book2->title,
        ]);

        $response->assertJsonMissing([
            'title' => $book3->title,
        ]);
    }

    public function test_ジャンルで書籍を検索できる(): void
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

        $response = $this->getJson('/api/v1/books?genre_id='.$technicalGenre->id);

        $response->assertOk();
        $response->assertJsonFragment([
            'title' => $laravelBook->title,
        ]);
        $response->assertJsonMissing([
            'title' => $novelBook->title,
        ]);
    }

    public function test_書籍一覧をページネーションで表示できる(): void
    {
        Book::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/books?per_page=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('meta.per_page', 10);
        $response->assertJsonPath('meta.total', 25);
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.last_page', 3);
    }

    public function test_キーワードに文字列以外を指定した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->getJson('/api/v1/books?keyword[]=Laravel');

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.keyword.0',
            'キーワードは文字列で入力してください。'
        );
    }

    public function test_キーワードが254文字の場合_書籍を検索できる(): void
    {
        $keyword = str_repeat('あ', 254);

        $book = Book::factory()->create([
            'title' => $keyword,
        ]);

        $response = $this->getJson('/api/v1/books?keyword='.$keyword);

        $response->assertOk();

        $response->assertJsonPath('data.0.id', $book->id);
        $response->assertJsonPath('data.0.title', $keyword);
    }

    public function test_キーワードが255文字の場合_書籍を検索できる(): void
    {
        $keyword = str_repeat('あ', 255);

        $book = Book::factory()->create([
            'title' => $keyword,
        ]);

        $response = $this->getJson('/api/v1/books?keyword='.$keyword);

        $response->assertOk();

        $response->assertJsonPath('data.0.id', $book->id);
        $response->assertJsonPath('data.0.title', $keyword);
    }

    public function test_キーワードが256文字の場合_バリデーションメッセージが表示される(): void
    {
        $keyword = str_repeat('あ', 256);

        $response = $this->getJson('/api/v1/books?keyword='.$keyword);

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.keyword.0',
            'キーワードは255文字以内で入力してください。'
        );
    }

    public function test_整数以外のジャンル_i_dを指定した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->getJson('/api/v1/books?genre_id=4.5');

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.genre_id.0',
            'ジャンルIDは整数で入力してください。'
        );
    }

    public function test_存在しないジャンル_i_dを指定した場合_バリデーションメッセージが表示される(): void
    {
        $genre = Genre::factory()->create();

        $nonExistentId = $genre->id + 999;

        $response = $this->getJson('/api/v1/books?genre_id='.$nonExistentId);

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.genre_id.0',
            '選択されたジャンルが存在しません。'
        );
    }

    public function test_ページ番号に整数以外の値を指定した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->getJson('/api/v1/books?page=1.5');

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.page.0',
            'ページ番号は整数で入力してください。'
        );
    }

    public function test_ページ番号を指定して書籍を取得できる(): void
    {
        Book::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/books?page=2&per_page=10');

        $response->assertOk();

        $response->assertJsonCount(10, 'data');
        $response->assertJsonPath('meta.current_page', 2);
        $response->assertJsonPath('meta.per_page', 10);
        $response->assertJsonPath('meta.total', 25);
    }

    public function test_表示件数に整数以外の値を指定した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=10.5');

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.per_page.0',
            '表示件数は整数で入力してください。'
        );
    }

    public function test_表示件数に0を指定した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=0');

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.per_page.0',
            '表示件数は1以上で入力してください。'
        );
    }

    public function test_表示件数に1を指定して書籍一覧を取得できる(): void
    {
        Book::factory()->count(15)->create();

        $response = $this->getJson('/api/v1/books?per_page=1');

        $response->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.per_page', 1);
        $response->assertJsonPath('meta.total', 15);
        $response->assertJsonPath('meta.last_page', 15);
    }

    public function test_表示件数に99を指定して書籍一覧を取得できる(): void
    {
        Book::factory()->count(100)->create();

        $response = $this->getJson('/api/v1/books?per_page=99');

        $response->assertOk();

        $response->assertJsonCount(99, 'data');
        $response->assertJsonPath('meta.per_page', 99);
        $response->assertJsonPath('meta.total', 100);
        $response->assertJsonPath('meta.last_page', 2);
    }

    public function test_表示件数に100を指定して書籍一覧を取得できる(): void
    {
        Book::factory()->count(101)->create();

        $response = $this->getJson('/api/v1/books?per_page=100');

        $response->assertOk();

        $response->assertJsonCount(100, 'data');
        $response->assertJsonPath('meta.per_page', 100);
        $response->assertJsonPath('meta.total', 101);
        $response->assertJsonPath('meta.last_page', 2);
    }

    public function test_表示件数に101を指定した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->getJson('/api/v1/books?per_page=101');

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.per_page.0',
            '表示件数は100以下で入力してください。'
        );
    }

    public function test_表示件数を省略した場合_20件ずつ取得される(): void
    {
        Book::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertOk();
        $response->assertJsonCount(20, 'data');
        $response->assertJsonPath('meta.per_page', 20);
        $response->assertJsonPath('meta.total', 25);
        $response->assertJsonPath('meta.last_page', 2);
    }
}
