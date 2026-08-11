<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookStoreApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Genre $genre;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->genre = Genre::factory()->create();
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'user_id' => $this->user->id,
            'title' => 'API登録タイトル',
            'author' => 'API登録著者',
            'isbn' => '9781111111111',
            'published_date' => '2026-08-01',
            'description' => 'API登録テキスト',
            'image_url' => 'https://example.com/store.jpg',
            'genres' => [$this->genre->id],
        ], $overrides);
    }

    public function test_書籍登録時の_jso_nレスポンス構造が正しい(): void
    {
        $response = $this->postJson(
            '/api/v1/books',
            $this->validData()
        );

        $response->assertStatus(201);

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

    public function test_書籍を登録できる(): void
    {
        $response = $this->postJson('/api/v1/books', $this->validData());

        $response->assertStatus(201);
        $response->assertJsonPath(
            'message',
            '書籍を登録しました。'
        );
        $response->assertJsonPath('data.title', 'API登録タイトル');

        $book = Book::where('title', 'API登録タイトル')->first();

        $this->assertNotNull($book);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $this->user->id,
            'title' => 'API登録タイトル',
            'isbn' => '9781111111111',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $this->genre->id,
        ]);
    }

    public function test_user_idが未指定の場合_バリデーションメッセージが表示される(): void
    {
        $data = $this->validData();

        unset($data['user_id']);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.user_id.0',
            '登録者IDを入力してください。'
        );
    }

    public function test_user_idに整数以外の値を入力した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->postJson(
            '/api/v1/books',
            $this->validData([
                'user_id' => 'aaa',
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.user_id.0',
            '登録者IDは整数で入力してください。'
        );
    }

    public function test_存在しないuser_idを入力した場合_バリデーションメッセージが表示される(): void
    {
        $nonExistentId = $this->user->id + 999;

        $response = $this->postJson(
            '/api/v1/books',
            $this->validData([
                'user_id' => $nonExistentId,
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.user_id.0',
            '指定された登録者IDが存在しません。'
        );
    }

    public function test_タイトルが未入力の場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->postJson(
            '/api/v1/books',
            $this->validData([
                'title' => '',
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.title.0',
            'タイトルを入力してください。'
        );
    }

    public function test_タイトルが文字列以外の値で指定された場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->postJson(
            '/api/v1/books',
            $this->validData([
                'title' => ['API登録タイトル'],
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.title.0',
            'タイトルは文字列で入力してください。'
        );
    }

    public function test_タイトルが256文字の場合_バリデーションメッセージが表示される(): void
    {
        $title = str_repeat('あ', 256);

        $response = $this->postJson(
            '/api/v1/books',
            $this->validData([
                'title' => $title,
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.title.0',
            'タイトルは255文字以内で入力してください。'
        );
    }

    public function test_タイトルが255文字の場合_書籍を登録できる(): void
    {
        $title = str_repeat('あ', 255);

        $response = $this->postJson('/api/v1/books', $this->validData([
            'title' => $title,
        ]));

        $response->assertStatus(201);
        $response->assertJsonPath('data.title', $title);

        $this->assertDatabaseHas('books', [
            'title' => $title,
        ]);
    }

    public function test_タイトルが254文字の場合_書籍を登録できる(): void
    {
        $title = str_repeat('あ', 254);

        $response = $this->postJson('/api/v1/books', $this->validData([
            'title' => $title,
        ]));

        $response->assertStatus(201);
        $response->assertJsonPath('data.title', $title);

        $this->assertDatabaseHas('books', [
            'title' => $title,
        ]);
    }

    public function test_著者名が未入力の場合_バリデーションメッセージが表示される(): void
    {
        $data = $this->validData();

        unset($data['author']);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.author.0',
            '著者名を入力してください。'
        );
    }

    public function test_著者名が文字列以外の値で指定された場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->postJson(
            '/api/v1/books',
            $this->validData([
                'author' => ['API登録著者'],
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.author.0',
            '著者名は文字列で入力してください。'
        );
    }

    public function test_著者名が256文字の場合_バリデーションメッセージが表示される(): void
    {
        $author = str_repeat('あ', 256);

        $response = $this->postJson(
            '/api/v1/books',
            $this->validData([
                'author' => $author,
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.author.0',
            '著者名は255文字以内で入力してください。'
        );
    }

    public function test_著者名が255文字の場合_書籍を登録できる(): void
    {
        $author = str_repeat('あ', 255);

        $response = $this->postJson('/api/v1/books', $this->validData([
            'author' => $author,
        ]));

        $response->assertStatus(201);
        $response->assertJsonPath('data.author', $author);

        $this->assertDatabaseHas('books', [
            'author' => $author,
        ]);
    }

    public function test_著者名が254文字の場合_書籍を登録できる(): void
    {
        $author = str_repeat('あ', 254);

        $response = $this->postJson('/api/v1/books', $this->validData([
            'author' => $author,
        ]));

        $response->assertStatus(201);
        $response->assertJsonPath('data.author', $author);

        $this->assertDatabaseHas('books', [
            'author' => $author,
        ]);
    }

    public function test_isbnが未入力の場合_バリデーションメッセージが表示される(): void
    {
        $data = $this->validData();

        unset($data['isbn']);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.isbn.0',
            'ISBNを入力してください。'
        );
    }

    public function test_isbnが12桁の場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->postJson(
            '/api/v1/books',
            $this->validData([
                'isbn' => '978012345678',
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.isbn.0',
            'ISBNは13桁で入力してください。'
        );
    }

    public function test_isbnが13桁の場合_書籍を登録できる(): void
    {
        $response = $this->postJson('/api/v1/books', $this->validData([
            'isbn' => '9780123456789',
        ]));

        $response->assertStatus(201);
        $response->assertJsonPath('data.isbn', '9780123456789');

        $this->assertDatabaseHas('books', [
            'isbn' => '9780123456789',
        ]);
    }

    public function test_既に登録済みのisbnを指定した場合_バリデーションメッセージが表示される(): void
    {
        Book::factory()->create([
            'isbn' => '9781111111111',
        ]);

        $response = $this->postJson(
            '/api/v1/books',
            $this->validData()
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.isbn.0',
            'このISBNは既に登録されています。'
        );
    }

    public function test_説明が文字列以外の場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->postJson(
            '/api/v1/books',
            $this->validData([
                'description' => ['API登録テキスト'],
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.description.0',
            '説明は文字列で入力してください。'
        );
    }

    public function test_出版日が未入力の場合_バリデーションメッセージが表示される(): void
    {
        $data = $this->validData();

        unset($data['published_date']);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.published_date.0',
            '出版日を入力してください。'
        );
    }

    public function test_出版日が不正な形式の場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->postJson(
            '/api/v1/books',
            $this->validData([
                'published_date' => 'aaa',
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.published_date.0',
            '出版日は有効な日付で入力してください。'
        );
    }

    public function test_画像_ur_lが不正な形式の場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->postJson(
            '/api/v1/books',
            $this->validData([
                'image_url' => 'aaa',
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.image_url.0',
            '画像URLは有効なURLで入力してください。'
        );
    }

    public function test_画像_ur_lが254文字の場合_書籍を登録できる(): void
    {
        $baseUrl = 'https://example.com/';
        $storeUrl = $baseUrl.str_repeat('a', 234);

        $response = $this->postJson('/api/v1/books', $this->validData([
            'image_url' => $storeUrl,
        ]));

        $response->assertStatus(201);
        $response->assertJsonPath('data.image_url', $storeUrl);

        $this->assertDatabaseHas('books', [
            'image_url' => $storeUrl,
        ]);
    }

    public function test_画像_ur_lが255文字の場合_書籍を登録できる(): void
    {
        $baseUrl = 'https://example.com/';
        $storeUrl = $baseUrl.str_repeat('a', 235);

        $response = $this->postJson('/api/v1/books', $this->validData([
            'image_url' => $storeUrl,
        ]));

        $response->assertStatus(201);
        $response->assertJsonPath('data.image_url', $storeUrl);

        $this->assertDatabaseHas('books', [
            'image_url' => $storeUrl,
        ]);
    }

    public function test_画像_ur_lが256文字の場合_バリデーションメッセージが表示される(): void
    {
        $baseUrl = 'https://example.com/';
        $storeUrl = $baseUrl.str_repeat('a', 236);

        $response = $this->postJson(
            '/api/v1/books',
            $this->validData([
                'image_url' => $storeUrl,
            ])
        );

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.image_url.0',
            '画像URLは255文字以内で入力してください。'
        );
    }

    public function test_ジャンルが未選択の場合_バリデーションメッセージが表示される(): void
    {
        $data = $this->validData();
        unset($data['genres']);

        $response = $this->postJson('/api/v1/books', $data);

        $response->assertStatus(422);
        $response->assertJsonPath(
            'errors.genres.0',
            'ジャンルを選択してください。'
        );
    }

    public function test_ジャンルに整数以外の値を指定した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->postJson(
            '/api/v1/books',
            $this->validData([
                'genres' => ['a'],
            ])
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'genres.0',
        ]);

        $response->assertJsonFragment([
            'ジャンルIDは整数で入力してください。',
        ]);
    }

    public function test_存在しないジャンルを指定した場合_バリデーションメッセージが表示される(): void
    {
        $nonExistentId = [
            $this->genre->id + 999,
        ];

        $response = $this->postJson(
            '/api/v1/books',
            $this->validData([
                'genres' => $nonExistentId,
            ])
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'genres.0',
        ]);

        $response->assertJsonFragment([
            '選択されたジャンルが存在しません。',
        ]);
    }
}
