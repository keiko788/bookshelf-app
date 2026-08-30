<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_未認証ユーザーが読書計画作成画面にアクセスした場合、ログイン画面へリダイレクトされる(): void
    {
        $response = $this->get(route('reading-plans.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーは読書計画作成画面を表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reading-plans.create'));

        $response->assertOk();
    }

    public function test_認証済みユーザーは読書計画を作成できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $targetDate = now()->addDays(3)->toDateString();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => $targetDate,
        ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
            'status' => ReadingPlanStatus::InProgress,
        ]);
    }

    public function test_書籍未選択で登録するとバリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $targetDate = now()->addDays(3)->toDateString();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'target_date' => $targetDate,
        ]);

        $response->assertSessionHasErrors([
            'book_id' => '書籍を選択してください。',
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'target_date' => $targetDate,
        ]);
    }

    public function test_期日を未入力で登録するとバリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => '',
        ]);

        $response->assertSessionHasErrors([
            'target_date' => '期日を入力してください。',
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_期日を不正な日付で登録するとバリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => 'aaa',
        ]);

        $response->assertSessionHasErrors([
            'target_date' => '期日は有効な日付で入力してください。',
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_期日を過去の日付で登録するとバリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $targetDate = now()->subDay()->toDateString();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => $targetDate,
        ]);

        $response->assertSessionHasErrors([
            'target_date' => '期日は今日以降の日付を指定してください。',
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => $targetDate,
        ]);
    }

    public function test_未完了の同一書籍の読書計画がある場合は新規登録できない(): void
    {
        $user = User::factory()->create();
        $inProgressBook = Book::factory()->create([
            'title' => '未完了の書籍',
        ]);

        $existingTargetDate = now()->addDays(3)->toDateString();
        $newTargetDate = now()->addDay()->toDateString();

        ReadingPlan::factory()
            ->for($user)
            ->for($inProgressBook)
            ->create([
                'target_date' => $existingTargetDate,
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ]);

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $inProgressBook->id,
            'target_date' => $newTargetDate,
        ]);

        $response->assertSessionHasErrors([
            'book_id' => 'この書籍には未完了の読書計画が既に存在します。',
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $inProgressBook->id,
            'target_date' => $existingTargetDate,
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $inProgressBook->id,
            'target_date' => $newTargetDate,
        ]);
    }
}
