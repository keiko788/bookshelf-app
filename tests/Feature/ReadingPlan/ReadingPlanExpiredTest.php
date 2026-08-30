<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanExpiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_期日を過ぎた読書計画に対して自動失効バッチを実行すると_ステータスが期限切れに変更される(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->create([
                'target_date' => now()->subDay()->toDateString(),
                'status' => ReadingPlanStatus::InProgress,
            ]);

        $this->artisan('reading-plans:update-expired')
            ->assertExitCode(0);

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $readingPlan->status,
        );
    }

    public function test_期日当日の読書計画は期限切れに変更されない(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->create([
                'target_date' => now()->addDay()->toDateString(),
                'status' => ReadingPlanStatus::InProgress,
            ]);

        $this->artisan('reading-plans:update-expired')
            ->assertExitCode(0);

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $readingPlan->status,
        );
    }

    public function test_完了済みの読書計画は期日を過ぎていても期限切れに変更されない(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->create([
                'target_date' => now()->subDay()->toDateString(),
                'status' => ReadingPlanStatus::Completed,
            ]);

        $this->artisan('reading-plans:update-expired')
            ->assertExitCode(0);

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $readingPlan->status,
        );
    }

    public function test_既に期限切れの読書計画は変更されない(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->create([
                'target_date' => now()->subDay()->toDateString(),
                'status' => ReadingPlanStatus::Expired,
            ]);

        $this->artisan('reading-plans:update-expired')
            ->assertExitCode(0);

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $readingPlan->status,
        );
    }
}
