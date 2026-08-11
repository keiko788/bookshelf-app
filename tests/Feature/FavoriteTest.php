<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_未認証ユーザーがお気に入りトグルを押下するとログイン画面へリダイレクトされる(): void
    {
        $book = Book::factory()->create();

        $response = $this->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーはお気に入りトグルを押下することにより_お気に入りを登録することができる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_登録済みの書籍のお気に入りトグルを再度押下することにより_お気に入りを解除することができる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();
        Favorite::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_認証済みユーザーはお気に入り一覧を10件ごとのページネーションで表示できる(): void
    {
        $user = User::factory()->create();

        $books = Book::factory()->count(11)->create();

        $user->favoriteBooks()->attach(
            $books->pluck('id')
        );

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertOk();
        $response->assertViewHas(
            'books',
            fn ($favorites) => $favorites->total() === 11
                && $favorites->perPage() === 10
                && $favorites->lastPage() === 2
        );
    }

    public function test_複数ユーザーがお気に入りを登録している状態で_自分のお気に入り一覧を表示することができる(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();
        $book3 = Book::factory()->create();

        $user->favoriteBooks()->attach([
            $book1->id,
            $book2->id,
        ]);
        $other->favoriteBooks()->attach($book3->id);

        $response = $this->actingAs($user)->get(route('favorites.index'));

        $response->assertOk();

        $response->assertSee($book1->title);
        $response->assertSee($book2->title);

        $response->assertDontSee($book3->title);

    }
}
