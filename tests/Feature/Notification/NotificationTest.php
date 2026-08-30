<?php

namespace Tests\Feature\Notification;

use App\Enums\ReadingPlanReminderTiming;
use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーは通知一覧画面を表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertOk();

        $response->assertViewIs('notifications.index');
    }

    public function test_通知一覧にはログインユーザー自身の通知のみが渡される(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->create([
                'target_date' => now()->addDays(3)->toDateString(),
                'status' => ReadingPlanStatus::InProgress,
            ]);

        $otherReadingPlan = ReadingPlan::factory()
            ->for($otherUser)
            ->create([
                'target_date' => now()->addDays(3)->toDateString(),
                'status' => ReadingPlanStatus::InProgress,
            ]);

        $user->notify(
            new ReadingPlanReminder(
                $readingPlan,
                ReadingPlanReminderTiming::ThreeDaysBefore
            )
        );

        $otherUser->notify(
            new ReadingPlanReminder(
                $otherReadingPlan,
                ReadingPlanReminderTiming::ThreeDaysBefore
            )
        );

        $notification = $user->notifications()->first();
        $otherNotification = $otherUser->notifications()->first();

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response->assertViewHas(
            'notifications',
            function ($notifications) use ($notification, $otherNotification) {
                return $notifications->contains('id', $notification->id)
                    && ! $notifications->contains('id', $otherNotification->id);
            }
        );
    }

    public function test_自分の未読通知を既読にできる(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->create([
                'target_date' => now()->addDays(3)->toDateString(),
                'status' => ReadingPlanStatus::InProgress,
            ]);

        $user->notify(
            new ReadingPlanReminder(
                $readingPlan,
                ReadingPlanReminderTiming::ThreeDaysBefore
            )
        );

        $notification = $user->notifications()->first();

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($user)->post(route('notifications.read', $notification->id));

        $response->assertRedirect();

        $notification->refresh();

        $this->assertNotNull($notification->read_at);
    }

    public function test_他人の通知は既読にできない(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherReadingPlan = ReadingPlan::factory()
            ->for($otherUser)
            ->create([
                'target_date' => now()->addDays(3)->toDateString(),
                'status' => ReadingPlanStatus::InProgress,
            ]);

        $otherUser->notify(
            new ReadingPlanReminder(
                $otherReadingPlan,
                ReadingPlanReminderTiming::ThreeDaysBefore
            )
        );

        $otherNotification = $otherUser->notifications()->first();

        $this->assertNull($otherNotification->read_at);

        $response = $this->actingAs($user)->post(route('notifications.read', $otherNotification->id));

        $response->assertNotFound();

        $otherNotification->refresh();

        $this->assertNull($otherNotification->read_at);
    }
}
