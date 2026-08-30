<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_未認証ユーザーが読書計画一覧画面にアクセスした場合、ログイン画面へリダイレクトされる(): void
    {
        $response = $this->get(route('reading-plans.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーは読書計画一覧画面を表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        $response->assertOk();
        $response->assertViewIs('reading-plans.index');
    }

    public function test_読書計画一覧をステータスで絞り込むことができる(): void
    {
        $user = User::factory()->create();

        $inProgressBook = Book::factory()->create([
            'title' => '進行中の書籍',
        ]);

        $completedBook = Book::factory()->create([
            'title' => '完了済みの書籍',
        ]);

        $expiredBook = Book::factory()->create([
            'title' => '期限切れの書籍',
        ]);

        ReadingPlan::factory()
            ->for($user)
            ->for($inProgressBook)
            ->create([
                'status' => ReadingPlanStatus::InProgress,
            ]);

        ReadingPlan::factory()
            ->for($user)
            ->for($completedBook)
            ->create([
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => now(),
            ]);

        ReadingPlan::factory()
            ->for($user)
            ->for($expiredBook)
            ->create([
                'status' => ReadingPlanStatus::Expired,
            ]);

        $response = $this->actingAs($user)->get('/reading-plans?status=completed');

        $response->assertOk();

        $response->assertSee('完了済みの書籍');
        $response->assertDontSee('進行中の書籍');
        $response->assertDontSee('期限切れの書籍');
    }

    public function test_読書計画は期限日の昇順に表示され_完了済みは最後に表示される(): void
    {
        $user = User::factory()->create();

        $threeDaysLaterBook = Book::factory()->create([
            'title' => '期限3日前の書籍',
        ]);

        $dueTodayBook = Book::factory()->create([
            'title' => '期限当日の書籍',
        ]);

        $threeDaysOverdueBook = Book::factory()->create([
            'title' => '期限3日後の書籍',
        ]);

        $completedBook = Book::factory()->create([
            'title' => '期限2日前かつ完了済みの書籍',
        ]);

        ReadingPlan::factory()
            ->for($user)
            ->for($threeDaysLaterBook)
            ->create([
                'target_date' => now()->addDays(3),
                'status' => ReadingPlanStatus::InProgress,
            ]);

        ReadingPlan::factory()
            ->for($user)
            ->for($dueTodayBook)
            ->create([
                'target_date' => now(),
                'status' => ReadingPlanStatus::InProgress,
            ]);

        ReadingPlan::factory()
            ->for($user)
            ->for($threeDaysOverdueBook)
            ->create([
                'target_date' => now()->subDays(3),
                'status' => ReadingPlanStatus::Expired,
            ]);

        ReadingPlan::factory()
            ->for($user)
            ->for($completedBook)
            ->create([
                'target_date' => now()->addDays(2),
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => now(),
            ]);

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        $response->assertOk();

        $response->assertSeeInOrder([
            '期限3日後の書籍',
            '期限当日の書籍',
            '期限3日前の書籍',
            '期限2日前かつ完了済みの書籍',
        ]);
    }

    public function test_自分の読書計画だけが表示される(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '自分の書籍',
        ]);

        $otherBook = Book::factory()->create([
            'title' => '他人の書籍',
        ]);

        ReadingPlan::factory()
            ->for($user)
            ->for($book)
            ->create();

        ReadingPlan::factory()
            ->for($otherUser)
            ->for($otherBook)
            ->create();

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        $response->assertOk();
        $response->assertSee('自分の書籍');
        $response->assertDontSee('他人の書籍');
    }
}
