<?php

namespace Tests\Feature\Reviews;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $reviewer;

    private Book $book;

    private Review $review;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reviewer = User::factory()->create();
        $this->book = Book::factory()->create();

        $this->review = Review::factory()
            ->for($this->reviewer)
            ->for($this->book)
            ->create([
                'rating' => 3,
                'comment' => '更新前コメント',
            ]);
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'rating' => 4,
            'comment' => '更新後コメント',
        ], $overrides);
    }

    public function test_レビュー投稿者本人はレビューを更新できる(): void
    {
        $response = $this->actingAs($this->reviewer)
            ->put(route('reviews.update', $this->review),
                $this->validData());

        $response->assertRedirect(route('books.show', $this->book));

        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'book_id' => $this->book->id,
            'user_id' => $this->reviewer->id,
            'rating' => 4,
            'comment' => '更新後コメント',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $this->review->id,
            'rating' => 3,
            'comment' => '更新前コメント',
        ]);
    }

    public function test_レビュー更新は投稿者だけが許可される(): void
    {
        $other = User::factory()->create();

        $this->assertTrue($this->reviewer->can('update', $this->review));
        $this->assertFalse($other->can('update', $this->review));
    }

    public function test_投稿者以外はレビューを更新できない(): void
    {
        $other = User::factory()->create();

        $response = $this->actingAs($other)
            ->put(
                route('reviews.update', $this->review),
                $this->validData()
            );

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'book_id' => $this->book->id,
            'user_id' => $this->reviewer->id,
            'rating' => 3,
            'comment' => '更新前コメント',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $this->review->id,
            'rating' => 4,
            'comment' => '更新後コメント',
        ]);
    }

    public function test_レビュー投稿者は評価1でレビューを更新できる(): void
    {
        $response = $this->actingAs($this->reviewer)
            ->put(
                route('reviews.update', $this->review),
                $this->validData([
                    'rating' => 1,
                ])
            );

        $response->assertRedirect(route('books.show', $this->book));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'book_id' => $this->book->id,
            'user_id' => $this->reviewer->id,
            'rating' => 1,
            'comment' => '更新後コメント',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $this->review->id,
            'rating' => 3,
            'comment' => '更新前コメント',
        ]);
    }

    public function test_レビュー投稿者は評価5でレビューを更新できる(): void
    {
        $response = $this->actingAs($this->reviewer)
            ->put(
                route('reviews.update', $this->review),
                $this->validData([
                    'rating' => 5,
                ])
            );

        $response->assertRedirect(route('books.show', $this->book));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'book_id' => $this->book->id,
            'user_id' => $this->reviewer->id,
            'rating' => 5,
            'comment' => '更新後コメント',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $this->review->id,
            'rating' => 3,
            'comment' => '更新前コメント',
        ]);
    }

    public function test_評価を選択せずでレビュー更新をした場合_バリデーションメッセージが表示される(): void
    {
        $data = $this->validData();

        unset($data['rating']);

        $response = $this->actingAs($this->reviewer)
            ->put(
                route('reviews.update', $this->review),
                $data
            );

        $response->assertSessionHasErrors([
            'rating' => '評価を選択してください。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'book_id' => $this->book->id,
            'user_id' => $this->reviewer->id,
            'rating' => 3,
            'comment' => '更新前コメント',
        ]);
    }

    public function test_評価に整数以外の値を入力してレビュー更新をした場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->reviewer)
            ->put(
                route('reviews.update', $this->review),
                $this->validData([
                    'rating' => 'a',
                ])
            );

        $response->assertSessionHasErrors([
            'rating' => '評価が不正です。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'book_id' => $this->book->id,
            'user_id' => $this->reviewer->id,
            'rating' => 3,
            'comment' => '更新前コメント',
        ]);
    }

    public function test_0の評価値でレビュー更新をした場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->reviewer)
            ->put(
                route('reviews.update', $this->review),
                $this->validData([
                    'rating' => 0,
                ])
            );

        $response->assertSessionHasErrors([
            'rating' => '評価は1から5の範囲で選択してください。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'book_id' => $this->book->id,
            'user_id' => $this->reviewer->id,
            'rating' => 3,
            'comment' => '更新前コメント',
        ]);
    }

    public function test_6の評価値でレビュー更新をした場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->reviewer)
            ->put(
                route('reviews.update', $this->review),
                $this->validData([
                    'rating' => 6,
                ])
            );

        $response->assertSessionHasErrors([
            'rating' => '評価は1から5の範囲で選択してください。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'book_id' => $this->book->id,
            'user_id' => $this->reviewer->id,
            'rating' => 3,
            'comment' => '更新前コメント',
        ]);
    }

    public function test_レビューを未入力で更新をした場合_バリデーションメッセージが表示される(): void
    {
        $response = $this->actingAs($this->reviewer)
            ->put(
                route('reviews.update', $this->review),
                $this->validData([
                    'comment' => '',
                ])
            );

        $response->assertSessionHasErrors([
            'comment' => 'レビューを入力してください。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'book_id' => $this->book->id,
            'user_id' => $this->reviewer->id,
            'rating' => 3,
            'comment' => '更新前コメント',
        ]);
    }

    public function test_254文字でレビューを更新できる(): void
    {
        $comment = str_repeat('あ', 254);

        $response = $this->actingAs($this->reviewer)
            ->put(
                route('reviews.update', $this->review),
                $this->validData([
                    'comment' => $comment,
                ])
            );

        $response->assertRedirect(route('books.show', $this->book));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'book_id' => $this->book->id,
            'user_id' => $this->reviewer->id,
            'rating' => 4,
            'comment' => $comment,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $this->review->id,
            'rating' => 3,
            'comment' => '更新前コメント',
        ]);
    }

    public function test_255文字でレビューを更新できる(): void
    {
        $comment = str_repeat('あ', 255);

        $response = $this->actingAs($this->reviewer)
            ->put(
                route('reviews.update', $this->review),
                $this->validData([
                    'comment' => $comment,
                ])
            );

        $response->assertRedirect(route('books.show', $this->book));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'book_id' => $this->book->id,
            'user_id' => $this->reviewer->id,
            'rating' => 4,
            'comment' => $comment,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $this->review->id,
            'rating' => 3,
            'comment' => '更新前コメント',
        ]);
    }

    public function test_256文字でレビューを更新した場合_バリデーションメッセージが表示される(): void
    {
        $comment = str_repeat('あ', 256);

        $response = $this->actingAs($this->reviewer)
            ->put(
                route('reviews.update', $this->review),
                $this->validData([
                    'comment' => $comment,
                ])
            );

        $response->assertSessionHasErrors([
            'comment' => 'レビューは255文字以内で入力してください。',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $this->review->id,
            'book_id' => $this->book->id,
            'user_id' => $this->reviewer->id,
            'rating' => 3,
            'comment' => '更新前コメント',
        ]);
    }
}
