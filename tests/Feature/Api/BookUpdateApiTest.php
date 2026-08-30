<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Genre $genre;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->genre = Genre::factory()->create([
            'name' => '更新前ジャンル',
        ]);

        $this->book = Book::factory()
            ->for($this->user)
            ->create();

        $this->book->genres()->attach($this->genre);
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'API更新後タイトル',
            'author' => 'API更新後著者',
            'isbn' => '9781111111111',
            'published_date' => '2026-08-01',
            'description' => 'API更新後テキスト',
            'image_url' => 'https://example.com/update.jpg',
            'genres' => [$this->genre->id],
        ], $overrides);
    }

    public function test_書籍更新時の_jso_nレスポンス構造が正しい(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson(
            "/api/v1/books/{$this->book->id}",
            $this->validData()
        );

        $response->assertOk();

        $response->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'user_id',
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
            ],
        ]);
    }

    public function test_書籍を更新できる(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $this->validData());

        $response->assertOk();
        $response->assertJsonPath(
            'message',
            '書籍を更新しました。'
        );
        $response->assertJsonPath('data.title', 'API更新後タイトル');

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'title' => 'API更新後タイトル',
            'isbn' => '9781111111111',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $this->book->id,
            'genre_id' => $this->genre->id,
        ]);
    }

    public function test_書籍の作成者以外は書籍を更新できない(): void
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();

        $otherBook = Book::factory()
            ->for($otherUser)
            ->create([
                'title' => '他人の書籍',
            ]);

        $response = $this->putJson("/api/v1/books/{$otherBook->id}", $this->validData());

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $otherBook->id,
            'user_id' => $otherUser->id,
            'title' => '他人の書籍',
        ]);
    }

    public function test_書籍のジャンルを更新できる(): void
    {
        Sanctum::actingAs($this->user);

        $newGenre = Genre::factory()->create([
            'name' => 'API更新後ジャンル',
        ]);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $this->validData([
            'genres' => [$newGenre->id],
        ]));

        $response->assertOk();

        $response->assertJsonPath('data.genres.0.name', 'API更新後ジャンル');

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'title' => 'API更新後タイトル',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $this->book->id,
            'genre_id' => $newGenre->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $this->book->id,
            'genre_id' => $this->genre->id,
        ]);
    }

    public function test_タイトルが未指定の場合_バリデーションメッセージが表示される(): void
    {
        Sanctum::actingAs($this->user);

        $data = $this->validData();

        unset($data['title']);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $data);

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.title.0',
            'タイトルを入力してください。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'title' => $this->book->title,
        ]);
    }

    public function test_タイトルが256文字の場合_バリデーションメッセージが表示される(): void
    {
        Sanctum::actingAs($this->user);

        $newTitle = str_repeat('あ', 256);

        $response = $this->putJson(
            "/api/v1/books/{$this->book->id}",
            $this->validData([
                'title' => $newTitle,
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.title.0',
            'タイトルは255文字以内で入力してください。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'title' => $this->book->title,
        ]);
    }

    public function test_タイトルが254文字の場合_書籍を更新できる(): void
    {
        Sanctum::actingAs($this->user);

        $newTitle = str_repeat('あ', 254);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $this->validData([
            'title' => $newTitle,
        ]));

        $response->assertOk();

        $response->assertJsonPath('data.title', $newTitle);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'title' => $newTitle,
            'isbn' => '9781111111111',
        ]);
    }

    public function test_タイトルが255文字の場合_書籍を更新できる(): void
    {
        Sanctum::actingAs($this->user);

        $newTitle = str_repeat('あ', 255);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $this->validData([
            'title' => $newTitle,
        ]));

        $response->assertOk();

        $response->assertJsonPath('data.title', $newTitle);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'title' => $newTitle,
            'isbn' => '9781111111111',
        ]);
    }

    public function test_著者名が未指定の場合_バリデーションメッセージが表示される(): void
    {
        Sanctum::actingAs($this->user);

        $data = $this->validData();

        unset($data['author']);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $data);

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.author.0',
            '著者名を入力してください。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'author' => $this->book->author,
        ]);
    }

    public function test_著者名が256文字の場合_バリデーションメッセージが表示される(): void
    {
        Sanctum::actingAs($this->user);

        $newAuthor = str_repeat('あ', 256);

        $response = $this->putJson(
            "/api/v1/books/{$this->book->id}",
            $this->validData([
                'author' => $newAuthor,
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.author.0',
            '著者名は255文字以内で入力してください。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'author' => $this->book->author,
        ]);
    }

    public function test_著者名が254文字の場合_書籍を更新できる(): void
    {
        Sanctum::actingAs($this->user);

        $newAuthor = str_repeat('あ', 254);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $this->validData([
            'author' => $newAuthor,
        ]));

        $response->assertOk();

        $response->assertJsonPath('data.author', $newAuthor);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'author' => $newAuthor,
        ]);
    }

    public function test_著者名が255文字の場合_書籍を更新できる(): void
    {
        Sanctum::actingAs($this->user);

        $newAuthor = str_repeat('あ', 255);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $this->validData([
            'author' => $newAuthor,
        ]));

        $response->assertOk();

        $response->assertJsonPath('data.author', $newAuthor);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'author' => $newAuthor,
        ]);
    }

    public function test_isbnが未入力でも書籍を更新できる(): void
    {
        Sanctum::actingAs($this->user);

        $data = $this->validData();

        unset($data['isbn']);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $data);

        $response->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'isbn' => null,
        ]);
    }

    public function test_isbnが13桁の場合_書籍を更新できる(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $this->validData([
            'isbn' => '9780123456789',
        ]));

        $response->assertOk();

        $response->assertJsonPath('data.isbn', '9780123456789');

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'isbn' => '9780123456789',
        ]);
    }

    public function test_isbnが12桁の場合_バリデーションメッセージが表示される(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson(
            "/api/v1/books/{$this->book->id}",
            $this->validData([
                'isbn' => '978012345678',
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.isbn.0',
            'ISBNは13桁で入力してください。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'isbn' => $this->book->isbn,
        ]);
    }

    public function test_既に登録済みのisbnを指定した場合_バリデーションメッセージが表示される(): void
    {
        Sanctum::actingAs($this->user);

        Book::factory()->create([
            'isbn' => '9781111111111',
        ]);
        $response = $this->putJson("/api/v1/books/{$this->book->id}", $this->validData());

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.isbn.0',
            'このISBNは既に登録されています。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'isbn' => $this->book->isbn,
        ]);
    }

    public function test_isbnを変更せずに書籍を更新できる(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $this->validData([
            'isbn' => $this->book->isbn,
        ]));

        $response->assertOk();

        $response->assertJsonPath('data.isbn', $this->book->isbn);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'isbn' => $this->book->isbn,
        ]);
    }

    public function test_出版日が未入力でも書籍を更新できる(): void
    {
        Sanctum::actingAs($this->user);

        $data = $this->validData();

        unset($data['published_date']);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $data);

        $response->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'published_date' => null,
        ]);
    }

    public function test_出版日が不正な形式の場合_バリデーションメッセージが表示される(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson(
            "/api/v1/books/{$this->book->id}",
            $this->validData([
                'published_date' => 'aaa',
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.published_date.0',
            '出版日は有効な日付で入力してください。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'published_date' => $this->book->published_date,
        ]);
    }

    public function test_画像_ur_lが不正な形式の場合_バリデーションメッセージが表示される(): void
    {
        Sanctum::actingAs($this->user);

        $this->book->update([
            'image_url' => 'https://example.com/before.jpg',
        ]);

        $response = $this->putJson(
            "/api/v1/books/{$this->book->id}",
            $this->validData([
                'image_url' => 'aaa',
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.image_url.0',
            '画像URLは有効なURLで入力してください。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'image_url' => 'https://example.com/before.jpg',
        ]);
    }

    public function test_画像_ur_lが254文字の場合_書籍を更新できる(): void
    {
        Sanctum::actingAs($this->user);

        $baseUrl = 'https://example.com/';
        $updateUrl = $baseUrl.str_repeat('a', 234);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $this->validData([
            'image_url' => $updateUrl,
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.image_url', $updateUrl);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'image_url' => $updateUrl,
        ]);
    }

    public function test_画像_ur_lが255文字の場合_書籍を更新できる(): void
    {
        Sanctum::actingAs($this->user);

        $baseUrl = 'https://example.com/';
        $updateUrl = $baseUrl.str_repeat('a', 235);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $this->validData([
            'image_url' => $updateUrl,
        ]));

        $response->assertOk();
        $response->assertJsonPath('data.image_url', $updateUrl);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'image_url' => $updateUrl,
        ]);
    }

    public function test_画像_ur_lが256文字の場合_バリデーションメッセージが表示される(): void
    {
        Sanctum::actingAs($this->user);

        $this->book->update([
            'image_url' => 'https://example.com/before.jpg',
        ]);

        $baseUrl = 'https://example.com/';
        $updateUrl = $baseUrl.str_repeat('a', 236);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $this->validData([
            'image_url' => $updateUrl,
        ]));

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.image_url.0',
            '画像URLは255文字以内で入力してください。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'image_url' => 'https://example.com/before.jpg',
        ]);
    }

    public function test_ジャンルが未入力の場合_バリデーションメッセージが表示される(): void
    {
        Sanctum::actingAs($this->user);

        $data = $this->validData();

        unset($data['genres']);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $data);

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.genres.0',
            'ジャンルを選択してください。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $this->book->id,
            'genre_id' => $this->genre->id,
        ]);
    }

    public function test_ジャンルに配列以外の値を指定した場合_バリデーションメッセージが表示される(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $this->validData([
            'genres' => 1,
        ]));

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.genres.0',
            'ジャンルは配列で入力してください。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $this->book->id,
            'genre_id' => $this->genre->id,
        ]);
    }

    public function test_ジャンル_i_dに整数以外の値を指定した場合_バリデーションメッセージが表示される(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $this->validData([
            'genres' => [1.5],
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'genres.0',
        ]);

        $response->assertJsonFragment([
            'ジャンルIDは整数で入力してください。',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $this->book->id,
            'genre_id' => $this->genre->id,
        ]);
    }

    public function test_存在しないジャンル_i_dを指定した場合_バリデーションメッセージが表示される(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $this->validData([
            'genres' => [9999],
        ]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'genres.0',
        ]);

        $response->assertJsonFragment([
            '選択されたジャンルが存在しません。',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $this->book->id,
            'genre_id' => $this->genre->id,
        ]);
    }

    public function test_存在しない書籍_i_dを更新した場合_404が返される(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/books/9999', $this->validData());

        $response->assertNotFound();
        $response->assertExactJson([
            'error' => '書籍が見つかりませんでした。',
        ]);
    }

    public function test_未認証で書籍を更新すると401が返る(): void
    {
        $response = $this->putJson("/api/v1/books/{$this->book->id}", $this->validData());

        $response->assertStatus(401);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => $this->book->title,
            'isbn' => $this->book->isbn,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $this->book->id,
            'title' => 'Api更新後タイトル',
            'isbn' => '9781111111111',
        ]);
    }
}
