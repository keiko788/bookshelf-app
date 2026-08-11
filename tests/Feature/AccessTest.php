<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍一覧画面にアクセスできる(): void
    {
        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertViewIs('books.index');
    }

    public function test_書籍詳細画面にアクセスできる(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertViewIs('books.show');
    }

    public function test_ランキング画面にアクセスできる(): void
    {
        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertViewIs('ranking.index');
    }

    public function test_認証済みユーザーは書籍登録画面にアクセスできる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('books.create'));

        $response->assertOk();
        $response->assertViewIs('books.create');
    }

    public function test_未認証ユーザーは書籍登録画面にアクセスするとログイン画面へリダイレクトされる(): void
    {
        $response = $this->get(route('books.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_未認証ユーザーは書籍登録をするとログイン画面へリダイレクトされる(): void
    {
        $response = $this->post(route('books.store'), [
            'title' => 'テストタイトル',
            'author' => 'テスト著者',
            'isbn' => '9780123456789',
            'published_date' => '2026-08-01',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーはジャンル一覧画面にアクセスできる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertOk();
        $response->assertViewIs('genres.index');
    }

    public function test_未認証ユーザーがジャンル一覧画面にアクセスした場合_ログイン画面へリダイレクトされる(): void
    {
        $response = $this->get(route('genres.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーはジャンル詳細画面にアクセスできる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertViewIs('genres.show');
    }

    public function test_未認証ユーザーはジャンル詳細画面にアクセスした場合_ログイン画面へリダイレクトされる(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->get(route('genres.show', $genre));

        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーはジャンル登録画面にアクセスできる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.create'));

        $response->assertOk();
        $response->assertViewIs('genres.create');
    }

    public function test_未認証ユーザーはジャンル登録画面にアクセスした場合_ログイン画面へリダイレクトされる(): void
    {
        $response = $this->get(route('genres.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_未認証ユーザーがジャンル登録した場合_ログイン画面へリダイレクトされる(): void
    {
        $response = $this->post(route('genres.store'));

        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーはジャンル編集画面にアクセスできる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.edit', $genre));

        $response->assertOk();
        $response->assertViewIs('genres.edit');
    }

    public function test_未認証ユーザーはジャンル編集画面にアクセスした場合_ログイン画面へリダイレクトされる(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->get(route('genres.edit', $genre));

        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーはお気に入り一覧画面にアクセスできる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertOk();
        $response->assertViewIs('favorites.index');
    }

    public function test_未認証ユーザーがお気に入り一覧画面にアクセスした場合_ログイン画面へリダイレクトされる(): void
    {
        $response = $this->get(route('favorites.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_未認証ユーザーがお気に入りを登録した場合_ログイン画面へリダイレクトされる(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('login'));
    }

    public function test_書籍の作成者は書籍編集画面にアクセスできる(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($owner)
            ->get(route('books.edit', $book));

        $response->assertOk();
    }

    public function test_書籍の作成者以外は書籍編集画面にアクセスできない(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $response = $this->actingAs($other)
            ->get(route('books.edit', $book));

        $response->assertForbidden();
    }

    public function test_未認証ユーザーは書籍編集画面にアクセスするとログイン画面へリダイレクトされる(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner)->create();

        $response = $this->get(route('books.edit', $book));

        $response->assertRedirect(route('login'));
    }

    public function test_レビュー投稿者本人はレビュー編集画面にアクセスできる(): void
    {
        $reviewer = User::factory()->create();
        $review = Review::factory()->for($reviewer)->create();

        $response = $this->actingAs($reviewer)
            ->get(route('reviews.edit', $review));

        $response->assertOk();
    }

    public function test_レビュー投稿者以外はレビュー編集画面にアクセスできない(): void
    {
        $reviewer = User::factory()->create();
        $other = User::factory()->create();
        $review = Review::factory()->for($reviewer)->create();

        $response = $this->actingAs($other)
            ->get(route('reviews.edit', $review));

        $response->assertForbidden();
    }

    public function test_未認証ユーザーはレビュー編集画面にアクセスするとログイン画面へリダイレクトされる(): void
    {
        $reviewer = User::factory()->create();
        $review = Review::factory()->for($reviewer)->create();

        $response = $this->get(route('reviews.edit', $review));

        $response->assertRedirect(route('login'));
    }

    public function test_未認証ユーザーがレビューを投稿すると_ログイン画面へリダイレクトされる(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(route('reviews.store', $book), [
            'rating' => 5,
            'comment' => 'テストコメント',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_未認証ユーザーがレビューいいねを押下すると_ログイン画面へリダイレクトされる(): void
    {
        $review = Review::factory()->create();

        $response = $this->post(route('reviews.like', $review));

        $response->assertRedirect(route('login'));
    }

    public function test_ログイン画面にアクセスできる(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertViewIs('auth.login');
    }

    public function test_会員登録画面にアクセスできる(): void
    {
        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertViewIs('auth.register');
    }

    public function test_認証済みユーザーがログイン画面にアクセスした場合_書籍一覧画面へリダイレクトされる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('books.index'));
    }
}
