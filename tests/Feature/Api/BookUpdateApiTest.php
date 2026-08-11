<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'user_id' => $this->user->id,
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

    public function test_書籍のジャンルを更新できる(): void
    {
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

    public function test_user_idが未指定の場合_バリデーションメッセージが表示される(): void
    {
        $data = $this->validData();

        unset($data['user_id']);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $data);

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.user_id.0',
            '登録者IDを入力してください。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'title' => $this->book->title,
        ]);
    }

    public function test_user_idに整数以外の数値を指定した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->putJson(
            "/api/v1/books/{$this->book->id}",
            $this->validData([
                'user_id' => 1.5,
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.user_id.0',
            '登録者IDは整数で入力してください。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'title' => $this->book->title,
        ]);
    }

    public function test_存在しないuser_idを指定した場合_バリデーションメッセージが表示される(): void
    {
        $nonExistentId = $this->user->id + 9999;

        $response = $this->putJson(
            "/api/v1/books/{$this->book->id}",
            $this->validData([
                'user_id' => $nonExistentId,
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.user_id.0',
            '指定された登録者IDが存在しません。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'title' => $this->book->title,
        ]);
    }

    public function test_タイトルが未指定の場合_バリデーションメッセージが表示される(): void
    {
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

    public function test_isbnが未指定の場合_バリデーションメッセージが表示される(): void
    {
        $data = $this->validData();

        unset($data['isbn']);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $data);

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.isbn.0',
            'ISBNを入力してください。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'isbn' => $this->book->isbn,
        ]);
    }

    public function test_isbnが13桁の場合_書籍を更新できる(): void
    {
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

    public function test_出版日が未指定の場合_バリデーションメッセージが表示される(): void
    {
        $data = $this->validData();

        unset($data['published_date']);

        $response = $this->putJson("/api/v1/books/{$this->book->id}", $data);

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.published_date.0',
            '出版日を入力してください。'
        );

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'user_id' => $this->user->id,
            'published_date' => $this->book->published_date,
        ]);
    }

    public function test_出版日が不正な形式の場合_バリデーションメッセージが表示される(): void
    {
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
        $response = $this->putJson('/api/v1/books/9999', $this->validData());

        $response->assertNotFound();
        $response->assertExactJson([
            'error' => '書籍が見つかりませんでした。',
        ]);
    }
}
