<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookUpdateTest extends TestCase
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
            'name' => 'テストジャンル',
        ]);

        $this->book = Book::factory()
            ->for($this->user)
            ->create([
                'title' => '更新前タイトル',
                'author' => '更新前著者',
                'isbn' => '9781111111111',
                'published_date' => '2026-08-01',
                'description' => 'テストテキスト',
                'image_url' => 'https://example.com/test_image.jpg',
            ]);

        $this->book->genres()->attach($this->genre);
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '9782222222222',
            'published_date' => '2026-08-02',
            'description' => '更新後テキスト',
            'image_url' => 'https://example.com/update.jpg',
            'genres' => [$this->genre->id],
        ], $overrides);
    }

    public function test_書籍の登録者は書籍編集画面で既存の書籍情報を確認できる(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('books.edit', $this->book));

        $response->assertOk();
        $response->assertSee($this->book->title);
        $response->assertSee($this->book->author);
        $response->assertSee($this->book->isbn);
        $response->assertSee($this->book->published_date->format('Y-m-d'));
        $response->assertSee($this->book->description);
        $response->assertSee($this->book->image_url);
        $response->assertSee($this->genre->name);
    }

    public function test_書籍の更新は書籍の登録者だけが許可される(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $this->assertTrue($owner->can('update', $book));
        $this->assertFalse($other->can('update', $book));
    }

    public function test_書籍の登録者は自分の書籍を更新できる(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData()
        );

        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
        ]);
    }

    public function test_書籍更新時ジャンルの紐付けを更新できる(): void
    {
        $newGenres = Genre::factory()->count(2)->create();

        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'genres' => $newGenres->pluck('id')->all(),
            ])
        );

        $response->assertRedirect(route('books.show', $this->book));

        foreach ($newGenres as $newGenre) {
            $this->assertDatabaseHas('book_genre', [
                'book_id' => $this->book->id,
                'genre_id' => $newGenre->id,
            ]);
        }

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $this->book->id,
            'genre_id' => $this->genre->id,
        ]);
    }

    public function test_書籍の登録者以外は書籍を更新できない(): void
    {
        $other = User::factory()->create();

        $response = $this->actingAs($other)->put(route('books.update', $this->book), $this->validData());

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => $this->book->title,
            'author' => $this->book->author,
            'isbn' => $this->book->isbn,
            'published_date' => $this->book->published_date,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $this->book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '9782222222222',
        ]);
    }

    public function test_タイトル未入力で書籍更新した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'title' => '',
            ])
        );

        $response->assertSessionHasErrors([
            'title' => 'タイトルを入力してください。',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => $this->book->title,
            'author' => $this->book->author,
            'isbn' => $this->book->isbn,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $this->book->id,
            'isbn' => '9782222222222',
        ]);
    }

    public function test_タイトルが254文字で書籍更新ができる(): void
    {
        $title = str_repeat('あ', 254);

        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'title' => $title,
            ])
        );

        $response->assertSessionDoesntHaveErrors('title');
        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('books', [
            'user_id' => $this->user->id,
            'title' => $title,
            'isbn' => '9782222222222',
        ]);
    }

    public function test_タイトルが255文字で書籍更新ができる(): void
    {
        $title = str_repeat('あ', 255);

        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'title' => $title,
            ])
        );

        $response->assertSessionDoesntHaveErrors('title');
        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('books', [
            'user_id' => $this->user->id,
            'title' => $title,
            'isbn' => '9782222222222',
        ]);
    }

    public function test_タイトルが256文字で書籍更新した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'title' => str_repeat('あ', 256),
            ])
        );

        $response->assertSessionHasErrors([
            'title' => 'タイトルは255文字以内で入力してください。',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => $this->book->title,
            'author' => $this->book->author,
            'isbn' => $this->book->isbn,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $this->book->id,
            'isbn' => '9782222222222',
        ]);
    }

    public function test_著者名が未入力で書籍更新した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'author' => '',
            ])
        );

        $response->assertSessionHasErrors([
            'author' => '著者名を入力してください。',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => $this->book->title,
            'author' => $this->book->author,
            'isbn' => $this->book->isbn,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $this->book->id,
            'isbn' => '9782222222222',
        ]);
    }

    public function test_著者名が254文字で書籍更新ができる(): void
    {
        $author = str_repeat('あ', 254);

        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'author' => $author,
            ])
        );

        $response->assertSessionDoesntHaveErrors('author');
        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('books', [
            'user_id' => $this->user->id,
            'author' => $author,
            'isbn' => '9782222222222',
        ]);
    }

    public function test_著者名が255文字で書籍更新ができる(): void
    {
        $author = str_repeat('あ', 255);

        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'author' => $author,
            ])
        );

        $response->assertSessionDoesntHaveErrors('author');
        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('books', [
            'user_id' => $this->user->id,
            'author' => $author,
            'isbn' => '9782222222222',
        ]);
    }

    public function test_著者名が256文字で書籍更新した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'author' => str_repeat('あ', 256),
            ])
        );

        $response->assertSessionHasErrors([
            'author' => '著者名は255文字以内で入力してください。',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => $this->book->title,
            'author' => $this->book->author,
            'isbn' => $this->book->isbn,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $this->book->id,
            'isbn' => '9782222222222',
        ]);
    }

    public function test_isbnを未入力でも書籍を更新できる(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'isbn' => '',
            ])
        );

        $response->assertSessionDoesntHaveErrors('isbn');
        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('books', [
            'user_id' => $this->user->id,
            'title' => '更新後タイトル',
            'isbn' => null,
        ]);
    }

    public function test_既に登録済みの_isbnで書籍更新した場合_バリデーションメッセージが表示される(): void
    {
        $oldBook = Book::factory()->create([
            'isbn' => '9780000000000',
        ]);

        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'isbn' => '9780000000000',
            ])
        );

        $response->assertSessionHasErrors([
            'isbn' => 'このISBNは既に登録されています。',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => $this->book->title,
            'author' => $this->book->author,
            'isbn' => $this->book->isbn,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $this->book->id,
            'isbn' => '9782222222222',
        ]);
    }

    public function test_isb_nが13桁の場合_書籍を更新できる(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'isbn' => '9780123456789',
            ])
        );

        $response->assertSessionDoesntHaveErrors('isbn');
        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('books', [
            'user_id' => $this->user->id,
            'isbn' => '9780123456789',
        ]);
    }

    public function test_自身の_isb_nを変更せずに書籍を更新できる(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'isbn' => '9781111111111',
            ])
        );

        $response->assertSessionDoesntHaveErrors('isbn');
        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('books', [
            'user_id' => $this->user->id,
            'isbn' => '9781111111111',
        ]);
    }

    public function test_isb_nが12桁で更新した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'isbn' => '978012345678',
            ])
        );

        $response->assertSessionHasErrors([
            'isbn' => 'ISBNは13桁で入力してください。',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => $this->book->title,
            'author' => $this->book->author,
            'isbn' => $this->book->isbn,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $this->book->id,
            'isbn' => '978012345678',
        ]);
    }

    public function test_出版日を未入力でも書籍を更新できる(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'published_date' => '',
            ])
        );

        $response->assertSessionDoesntHaveErrors('published_date');
        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('books', [
            'user_id' => $this->user->id,
            'title' => '更新後タイトル',
            'published_date' => null,
        ]);
    }

    public function test_出版日を不正な形式で更新した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'published_date' => 'aaa',
            ])
        );

        $response->assertSessionHasErrors([
            'published_date' => '出版日は有効な日付で入力してください。',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => $this->book->title,
            'author' => $this->book->author,
            'isbn' => $this->book->isbn,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $this->book->id,
            'published_date' => 'aaa',
            'isbn' => '9782222222222',
        ]);
    }

    public function test_ジャンルを選択せずに更新した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'genres' => [],
            ])
        );

        $response->assertSessionHasErrors([
            'genres' => 'ジャンルを選択してください。',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => $this->book->title,
            'author' => $this->book->author,
            'isbn' => $this->book->isbn,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $this->book->id,
            'isbn' => '9782222222222',
        ]);
    }

    public function test_ジャンルを不正な形式で更新した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'genres' => '1',
            ])
        );

        $response->assertSessionHasErrors([
            'genres' => 'ジャンルの指定が正しくありません。',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => $this->book->title,
            'author' => $this->book->author,
            'isbn' => $this->book->isbn,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $this->book->id,
            'isbn' => '9782222222222',
        ]);
    }

    public function test_存在しないジャンルを指定して更新した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'genres' => [9999],
            ])
        );

        $response->assertSessionHasErrors([
            'genres.0' => '選択されたジャンルが存在しません。',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => $this->book->title,
            'author' => $this->book->author,
            'isbn' => $this->book->isbn,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $this->book->id,
            'isbn' => '9782222222222',
        ]);
    }

    public function test_不正な画像_ur_lを指定して更新した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'image_url' => 'aaa',
            ])
        );

        $response->assertSessionHasErrors([
            'image_url' => '画像URLは有効なURLを入力してください。',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => $this->book->title,
            'author' => $this->book->author,
            'isbn' => $this->book->isbn,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $this->book->id,
            'isbn' => '9782222222222',
        ]);
    }

    public function test_画像_ur_lが254文字の場合_書籍を更新できる(): void
    {
        $baseUrl = 'https://example.com/';
        $updateUrl = $baseUrl.str_repeat('a', 234);

        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'image_url' => $updateUrl,
            ])
        );

        $response->assertSessionDoesntHaveErrors('image_url');
        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('books', [
            'user_id' => $this->user->id,
            'image_url' => $updateUrl,
        ]);
    }

    public function test_画像_ur_lが255文字の場合_書籍を更新できる(): void
    {
        $baseUrl = 'https://example.com/';
        $updateUrl = $baseUrl.str_repeat('a', 235);

        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'image_url' => $updateUrl,
            ])
        );

        $response->assertSessionDoesntHaveErrors('image_url');
        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('books', [
            'user_id' => $this->user->id,
            'image_url' => $updateUrl,
        ]);
    }

    public function test_画像_ur_lが256文字で更新した場合_バリデーションメッセージが表示される(): void
    {
        $baseUrl = 'https://example.com/';
        $updateUrl = $baseUrl.str_repeat('a', 236);

        $response = $this->actingAs($this->user)->put(
            route('books.update', $this->book),
            $this->validData([
                'image_url' => $updateUrl,
            ])
        );

        $response->assertSessionHasErrors([
            'image_url' => '画像URLは255文字以内で入力してください。',
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $this->book->id,
            'title' => $this->book->title,
            'author' => $this->book->author,
            'isbn' => $this->book->isbn,
        ]);

        $this->assertDatabaseMissing('books', [
            'id' => $this->book->id,
            'isbn' => '9782222222222',
        ]);
    }
}
