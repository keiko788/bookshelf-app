<?php

namespace Tests\Feature\Books;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍登録画面に登録済みの全ジャンルが表示される(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('books.create'));

        $response->assertOk();
        $response->assertViewHas('genres');

        foreach ($genres as $genre) {
            $response->assertSee($genre->name);
        }
    }

    public function test_認証済みユーザーは必須項目を正しく入力すると書籍を登録できる(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '9787654321234',
            'published_date' => '2026-08-01',
            'genres' => $genres->pluck('id')->toArray(),
        ]);

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '9787654321234',
            'published_date' => '2026-08-01',
        ]);

        $book = Book::where('isbn', '9787654321234')->first();
        $this->assertNotNull($book);

        foreach ($genres as $genre) {
            $this->assertDatabaseHas('book_genre', [
                'book_id' => $book->id,
                'genre_id' => $genre->id,
            ]);
        }
    }

    public function test_タイトル未入力で書籍登録した場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => '',
            'author' => 'テスト著者',
            'isbn' => '9781111111111',
            'published_date' => '2026-08-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors([
            'title' => 'タイトルを入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'user_id' => $user->id,
            'isbn' => '9781111111111',
        ]);
    }

    public function test_タイトルが254文字なら登録できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => str_repeat('あ', 254),
            'author' => 'テスト著者',
            'isbn' => '9781111111111',
            'published_date' => '2026-08-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionDoesntHaveErrors('title');
        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'isbn' => '9781111111111',
        ]);
    }

    public function test_タイトルが255文字なら登録できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => str_repeat('あ', 255),
            'author' => 'テスト著者',
            'isbn' => '9781111111111',
            'published_date' => '2026-08-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionDoesntHaveErrors('title');
        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'isbn' => '9781111111111',
        ]);
    }

    public function test_タイトルを256文字で登録した場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => str_repeat('あ', 256),
            'author' => 'テスト著者',
            'isbn' => '9781111111111',
            'published_date' => '2026-08-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors([
            'title' => 'タイトルは255文字以内で入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'user_id' => $user->id,
            'isbn' => '9781111111111',
        ]);
    }

    public function test_著者名を未入力で書籍登録した場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => '',
            'isbn' => '9781111111111',
            'published_date' => '2026-08-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors([
            'author' => '著者名を入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'user_id' => $user->id,
            'isbn' => '9781111111111',
        ]);
    }

    public function test_著者名が254文字なら登録できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => str_repeat('あ', 254),
            'isbn' => '9781111111111',
            'published_date' => '2026-08-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionDoesntHaveErrors('author');
        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'isbn' => '9781111111111',
        ]);
    }

    public function test_著者名が255文字なら登録できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => str_repeat('あ', 255),
            'isbn' => '9781111111111',
            'published_date' => '2026-08-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionDoesntHaveErrors('author');
        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'isbn' => '9781111111111',
        ]);
    }

    public function test_著者名を256文字で登録した場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => str_repeat('あ', 256),
            'isbn' => '9781111111111',
            'published_date' => '2026-08-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors([
            'author' => '著者名は255文字以内で入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'user_id' => $user->id,
            'isbn' => '9781111111111',
        ]);
    }

    public function test_isb_nを未入力で書籍登録した場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '',
            'published_date' => '2026-08-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors([
            'isbn' => 'ISBNを入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'user_id' => $user->id,
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
        ]);
    }

    public function test_既に登録済みの_isb_nで登録を実行した場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        Book::factory()->create([
            'isbn' => '9781111111111',
        ]);

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '9781111111111',
            'published_date' => '2026-08-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors([
            'isbn' => 'このISBNは既に登録されています。',
        ]);

        $this->assertDatabaseMissing('books', [
            'user_id' => $user->id,
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
        ]);
    }

    public function test_isb_nが13桁の場合_書籍を登録できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '9780123456789',
            'published_date' => '2026-08-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionDoesntHaveErrors('isbn');
        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'isbn' => '9780123456789',
        ]);
    }

    public function test_isb_nが12桁で登録を実行した場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '978012345678',
            'published_date' => '2026-08-01',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors([
            'isbn' => 'ISBNは13桁で入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'user_id' => $user->id,
            'isbn' => '978012345678',
        ]);
    }

    public function test_出版日が未入力の場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '9780123456789',
            'published_date' => '',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors([
            'published_date' => '出版日を入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'user_id' => $user->id,
            'isbn' => '9780123456789',
        ]);
    }

    public function test_出版日が不正な形式の場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '9780123456789',
            'published_date' => 'aaa',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors([
            'published_date' => '出版日は有効な日付で入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'user_id' => $user->id,
            'isbn' => '9780123456789',
        ]);
    }

    public function test_ジャンルを選択せずに登録した場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '9780123456789',
            'published_date' => '2026-08-01',
        ]);

        $response->assertSessionHasErrors([
            'genres' => 'ジャンルを選択してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'user_id' => $user->id,
            'isbn' => '9780123456789',
        ]);
    }

    public function test_ジャンルを不正な形式で送信した場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '9780123456789',
            'published_date' => '2026-08-01',
            'genres' => $genre->id,
        ]);

        $response->assertSessionHasErrors([
            'genres' => 'ジャンルの指定が正しくありません。',
        ]);

        $this->assertDatabaseMissing('books', [
            'user_id' => $user->id,
            'isbn' => '9780123456789',
        ]);
    }

    public function test_ジャンルを1件も選択せずに登録した場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '9780123456789',
            'published_date' => '2026-08-01',
            'genres' => [],
        ]);

        $response->assertSessionHasErrors([
            'genres' => 'ジャンルを選択してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'user_id' => $user->id,
            'isbn' => '9780123456789',
        ]);
    }

    public function test_存在しないジャンルを指定して登録をした場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '9780123456789',
            'published_date' => '2026-08-01',
            'genres' => [999],
        ]);

        $response->assertSessionHasErrors([
            'genres.0' => '選択されたジャンルが存在しません。',
        ]);

        $this->assertDatabaseMissing('books', [
            'user_id' => $user->id,
            'isbn' => '9780123456789',
        ]);
    }

    public function test_不正な画像_ur_lを指定して登録をした場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '9780123456789',
            'published_date' => '2026-08-01',
            'image_url' => 'aaa',
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors([
            'image_url' => '画像URLは有効なURLを入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'user_id' => $user->id,
            'isbn' => '9780123456789',
        ]);
    }

    public function test_画像_ur_lが254文字の場合_書籍を登録できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $baseUrl = 'https://example.com/';

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '9780123456789',
            'published_date' => '2026-08-01',
            'image_url' => $baseUrl.str_repeat('a', 234),
            'genres' => [$genre->id],
        ]);

        $response->assertSessionDoesntHaveErrors('image_url');
        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'isbn' => '9780123456789',
        ]);
    }

    public function test_画像_ur_lが255文字の場合_書籍を登録できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $baseUrl = 'https://example.com/';

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '9780123456789',
            'published_date' => '2026-08-01',
            'image_url' => $baseUrl.str_repeat('a', 235),
            'genres' => [$genre->id],
        ]);

        $response->assertSessionDoesntHaveErrors('image_url');
        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'isbn' => '9780123456789',
        ]);
    }

    public function test_画像_ur_lを256文字で登録をした場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $baseUrl = 'https://example.com/';

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '9780123456789',
            'published_date' => '2026-08-01',
            'image_url' => $baseUrl.str_repeat('a', 236),
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors([
            'image_url' => '画像URLは255文字以内で入力してください。',
        ]);

        $this->assertDatabaseMissing('books', [
            'user_id' => $user->id,
            'isbn' => '9780123456789',
        ]);
    }
}
