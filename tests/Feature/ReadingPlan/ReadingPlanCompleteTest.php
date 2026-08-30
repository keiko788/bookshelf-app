<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanCompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_所有者本人は自分の読書計画を読了にできる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->for($book)
            ->create([
                'target_date' => now()->addDay(),
                'status' => ReadingPlanStatus::InProgress,
            ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan->id));

        $response->assertRedirect(route('reading-plans.index'));

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $readingPlan->status
        );

        $this->assertNotNull($readingPlan->completed_at);
    }

    public function test_所有者以外が読書計画を読了しようとすると403エラーになりステータスは変更されない(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherReadingPlan = ReadingPlan::factory()
            ->for($otherUser)
            ->create([
                'target_date' => now()->addDays(3),
                'status' => ReadingPlanStatus::InProgress,
            ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $otherReadingPlan->id));

        $response->assertForbidden();

        $otherReadingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $otherReadingPlan->status
        );

        $this->assertNull($otherReadingPlan->completed_at);
    }

    public function test_未認証ユーザーが読書計画を読了しようとするとログイン画面へリダイレクトされる(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->create([
                'target_date' => now()->addDays(3),
                'status' => ReadingPlanStatus::InProgress,
            ]);

        $response = $this->post(route('reading-plans.complete', $readingPlan->id));

        $response->assertRedirect(route('login'));

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $readingPlan->status
        );

        $this->assertNull($readingPlan->completed_at);
    }
}
