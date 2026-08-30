<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanReminderTiming;
use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_期日3日前の読書計画にリマインダー通知が作成される(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->for($book)
            ->create([
                'target_date' => now()->addDays(3)->toDateString(),
                'status' => ReadingPlanStatus::InProgress,
            ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertExitCode(0);

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );
        $this->assertSame(
            ReadingPlanReminderTiming::ThreeDaysBefore->value,
            $notification->data['timing']
        );
        $this->assertSame(
            '読書期限が近づいています',
            $notification->data['title']
        );
        $this->assertSame(
            '「Laravel入門」の読書期限まであと3日です。',
            $notification->data['body']
        );

    }

    public function test_期限当日の読書計画にリマインダー通知が送信される(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->for($book)
            ->create([
                'target_date' => now()->toDateString(),
                'status' => ReadingPlanStatus::InProgress,
            ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertExitCode(0);

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );
        $this->assertSame(
            ReadingPlanReminderTiming::OnDueDate->value,
            $notification->data['timing']
        );
        $this->assertSame(
            '本日が読書期限です',
            $notification->data['title']
        );
        $this->assertSame(
            '「Laravel入門」は本日が読書期限です。',
            $notification->data['body']
        );

    }

    public function test_期限3日後の読書計画にリマインダー通知が送信される(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->for($book)
            ->create([
                'target_date' => now()->subDays(3)->toDateString(),
                'status' => ReadingPlanStatus::Expired,
            ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertExitCode(0);

        $notification = $user->notifications()->first();

        $this->assertNotNull($notification);
        $this->assertSame(
            $readingPlan->id,
            $notification->data['reading_plan_id']
        );
        $this->assertSame(
            ReadingPlanReminderTiming::ThreeDaysAfter->value,
            $notification->data['timing']
        );
        $this->assertSame(
            '読書期限を過ぎています',
            $notification->data['title']
        );
        $this->assertSame(
            '「Laravel入門」の読書期限を3日過ぎています。',
            $notification->data['body']
        );

    }

    public function test_完了済みの読書計画にはリマインダー通知が送信されない(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        ReadingPlan::factory()
            ->for($user)
            ->for($book)
            ->create([
                'target_date' => now()->toDateString(),
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => now(),
            ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertExitCode(0);

        $this->assertTrue(
            $user->notifications()->doesntExist()
        );
    }

    public function test_同じ読書計画の同じタイミングのリマインダー通知は重複して送信されない(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->for($book)
            ->create([
                'target_date' => now()->addDays(3)->toDateString(),
                'status' => ReadingPlanStatus::InProgress,
            ]);

        $this->artisan('reading-plans:send-reminders')
            ->assertExitCode(0);

        $this->artisan('reading-plans:send-reminders')
            ->assertExitCode(0);

        $notifications = $user->notifications()->get();

        $this->assertSame(
            $readingPlan->id,
            $notifications->first()->data['reading_plan_id']
        );

        $this->assertSame(
            ReadingPlanReminderTiming::ThreeDaysBefore->value,
            $notifications->first()->data['timing']
        );
    }
}
