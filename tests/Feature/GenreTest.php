<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーはジャンル一覧を書籍数付きで取得できる(): void
    {
        $user = User::factory()->create();

        $genre1 = Genre::factory()->create(['name' => '小説']);
        $genre2 = Genre::factory()->create(['name' => '技術書']);

        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();
        $book3 = Book::factory()->create();

        $genre1->books()->attach([$book1->id, $book2->id]);
        $genre2->books()->attach($book3->id);

        $response = $this->actingAs($user)
            ->get(route('genres.index'));

        $response->assertOk();
        $response->assertSee('小説');
        $response->assertSee('技術書');
        $response->assertSee('2');
        $response->assertSee('1');
    }

    public function test_認証済みユーザーはジャンル詳細を取得できる(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create(['name' => '詳細テスト']);

        $books = Book::factory()->count(3)->create();

        $genre->books()->attach($books);

        $response = $this->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertSee('詳細テスト');
        foreach ($books as $book) {
            $response->assertSee($book->title);
        }
    }

    public function test_ジャンル詳細で紐づく書籍が10件ごとのページネーションで表示できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $books = Book::factory()->count(11)->create();

        $genre->books()->attach($books);

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertViewHas(
            'books',
            fn ($viewBooks) => $viewBooks->total() === 11
                && $viewBooks->perPage() === 10
                && $viewBooks->lastPage() === 2
        );
    }

    public function test_認証済みユーザーはジャンルを登録できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => 'テストジャンル',
            ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'name' => 'テストジャンル',
        ]);
    }

    public function test_ジャンル名を未入力で登録した場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $beforeCount = Genre::count();

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '',
            ]);
        $response->assertSessionHasErrors([
            'name' => 'ジャンル名を入力してください。',
        ]);

        $this->assertDatabaseCount('genres', $beforeCount);
    }

    public function test_ジャンル名を254文字で登録できる(): void
    {
        $user = User::factory()->create();
        $name = str_repeat('あ', 254);

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => $name,
            ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'name' => $name,
        ]);
    }

    public function test_ジャンル名を255文字で登録できる(): void
    {
        $user = User::factory()->create();
        $name = str_repeat('あ', 255);

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => $name,
            ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'name' => $name,
        ]);
    }

    public function test_ジャンル名を256文字で登録した場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $beforeCount = Genre::count();

        $name = str_repeat('あ', 256);

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => $name,
            ]);

        $response->assertSessionHasErrors([
            'name' => 'ジャンル名は255文字以内で入力してください。',
        ]);

        $this->assertDatabaseCount('genres', $beforeCount);
    }

    public function test_既に登録済みのジャンル名で登録した場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '登録済みジャンル',
        ]);
        $beforeCount = Genre::count();

        $response = $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '登録済みジャンル',
            ]);

        $response->assertSessionHasErrors([
            'name' => 'このジャンル名は既に登録されています。',
        ]);

        $this->assertDatabaseCount('genres', $beforeCount);
    }

    public function test_認証済みユーザーはジャンル編集画面で現在のジャンル名を確認できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => 'テストジャンル',
        ]);

        $response = $this->actingAs($user)
            ->get(route('genres.edit', $genre));

        $response->assertOk();
        $response->assertSee('テストジャンル');
    }

    public function test_認証済みユーザーはジャンルを更新できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '更新前ジャンル',
        ]);

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '更新後ジャンル',
            ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新後ジャンル',
        ]);
        $this->assertDatabaseMissing('genres', [
            'name' => '更新前ジャンル',
        ]);
    }

    public function test_ジャンルを未入力で更新した場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '更新前ジャンル',
        ]);

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '',
            ]);

        $response->assertSessionHasErrors([
            'name' => 'ジャンル名を入力してください。',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新前ジャンル',
        ]);
    }

    public function test_ジャンル名を254文字で更新できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '更新前ジャンル',
        ]);
        $name = str_repeat('あ', 254);

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => $name,
            ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'name' => $name,
        ]);
        $this->assertDatabaseMissing('genres', [
            'name' => '更新前ジャンル',
        ]);
    }

    public function test_ジャンル名を255文字で更新できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '更新前ジャンル',
        ]);

        $name = str_repeat('あ', 255);

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => $name,
            ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'name' => $name,
        ]);
        $this->assertDatabaseMissing('genres', [
            'name' => '更新前ジャンル',
        ]);
    }

    public function test_ジャンル名を256文字で更新した場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '更新前ジャンル',
        ]);

        $name = str_repeat('あ', 256);

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => $name,
            ]);

        $response->assertSessionHasErrors([
            'name' => 'ジャンル名は255文字以内で入力してください。',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新前ジャンル',
        ]);
        $this->assertDatabaseMissing('genres', [
            'name' => $name,
        ]);
    }

    public function test_既に登録済みのジャンル名で更新した場合_バリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '更新前ジャンル',
        ]);
        Genre::factory()->create([
            'name' => '登録済みジャンル',
        ]);

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '登録済みジャンル',
            ]);

        $response->assertSessionHasErrors([
            'name' => 'このジャンル名は既に登録されています。',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新前ジャンル',
        ]);
    }

    public function test_ジャンル名を変更せずに更新する(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create([
            'name' => '更新前ジャンル',
        ]);

        $response = $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '更新前ジャンル',
            ]);

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新前ジャンル',
        ]);
    }

    public function test_認証済みユーザーが書籍と紐付きがないジャンルを削除する(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_書籍と紐付きがあるジャンルは削除できない(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();

        $book->genres()->attach($genre);

        $response = $this->actingAs($user)
            ->from(route('genres.index'))
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas(
            'error',
            '書籍に紐付いているジャンルは削除できません。'
        );

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }
}
