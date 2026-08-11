<?php

namespace Tests\Feature\Reviews;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Book $book;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->book = Book::factory()->create();
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'rating' => 3,
            'comment' => 'テストコメント',
        ], $overrides);
    }

    public function test_認証済みユーザーは評価1でレビューを投稿できる(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('reviews.store', $this->book),
            $this->validData([
                'rating' => 1,
            ])
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'rating' => 1,
            'comment' => 'テストコメント',
        ]);
    }

    public function test_認証済みユーザーは評価5でレビューを投稿できる(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('reviews.store', $this->book),
            $this->validData([
                'rating' => 5,
            ])
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'rating' => 5,
            'comment' => 'テストコメント',
        ]);
    }

    public function test_評価を選択せずにレビューを投稿した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('reviews.store', $this->book),
            $this->validData([
                'rating' => '',
            ])
        );

        $response->assertSessionHasErrors([
            'rating' => '評価を選択してください。',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'comment' => 'テストコメント',
        ]);
    }

    public function test_評価に整数以外の値を指定して投稿した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('reviews.store', $this->book),
            $this->validData([
                'rating' => 'a',
            ])
        );

        $response->assertSessionHasErrors([
            'rating' => '評価が不正です。',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'comment' => 'テストコメント',
        ]);
    }

    public function test_評価値0でレビューを投稿した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('reviews.store', $this->book),
            $this->validData([
                'rating' => 0,
            ])
        );

        $response->assertSessionHasErrors([
            'rating' => '評価は1から5の範囲で選択してください。',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'comment' => 'テストコメント',
        ]);
    }

    public function test_評価値6でレビューを投稿した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('reviews.store', $this->book),
            $this->validData([
                'rating' => 6,
            ])
        );

        $response->assertSessionHasErrors([
            'rating' => '評価は1から5の範囲で選択してください。',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'comment' => 'テストコメント',
        ]);
    }

    public function test_評価を選択し_レビュー未入力で投稿した場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('reviews.store', $this->book),
            $this->validData([
                'comment' => '',
            ])
        );

        $response->assertSessionHasErrors([
            'comment' => 'レビューを入力してください。',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'comment' => 'テストコメント',
        ]);
    }

    public function test_254文字でレビューを投稿できる(): void
    {
        $comment = str_repeat('あ', 254);

        $response = $this->actingAs($this->user)->post(
            route('reviews.store', $this->book),
            $this->validData([
                'comment' => $comment,
            ])
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'comment' => $comment,
        ]);
    }

    public function test_255文字でレビューを投稿できる(): void
    {
        $comment = str_repeat('あ', 255);

        $response = $this->actingAs($this->user)->post(
            route('reviews.store', $this->book),
            $this->validData([
                'comment' => $comment,
            ])
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'comment' => $comment,
        ]);
    }

    public function test_256文字でレビューを投稿した場合_バリデーションメッセージが表示される(): void
    {
        $comment = str_repeat('あ', 256);

        $response = $this->actingAs($this->user)->post(
            route('reviews.store', $this->book),
            $this->validData([
                'comment' => $comment,
            ])
        );

        $response->assertSessionHasErrors([
            'comment' => 'レビューは255文字以内で入力してください。',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'user_id' => $this->user->id,
            'book_id' => $this->book->id,
            'comment' => $comment,
        ]);
    }
}
