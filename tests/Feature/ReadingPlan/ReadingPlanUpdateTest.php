<?php

namespace Tests\Feature\ReadingPlan;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_読書計画編集画面では既存の期日が初期値として表示される(): void
    {
        $user = User::factory()->create();

        $targetDate = now()->addDays(3)->toDateString();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->create([
                'target_date' => $targetDate,
            ]);

        $response = $this->actingAs($user)->get(route('reading-plans.edit', $readingPlan->id));

        $response->assertOk();
        $response->assertSee('value="'.$targetDate.'"', false);
    }

    public function test_所有者本人は読書計画編集画面にアクセスできる(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->create();

        $response = $this->actingAs($user)->get(route('reading-plans.edit', $readingPlan->id));

        $response->assertOk();
        $response->assertViewIs('reading-plans.edit');
    }

    public function test_所有者以外が読書計画編集画面にアクセスすると403エラーになる(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherReadingPlan = ReadingPlan::factory()
            ->for($otherUser)
            ->create();

        $response = $this->actingAs($user)->get(route('reading-plans.edit', $otherReadingPlan->id));

        $response->assertForbidden();
    }

    public function test_所有者本人が期日を変更すると更新される(): void
    {
        $user = User::factory()->create();

        $targetDate = now()->addDays(3)->toDateString();
        $newTargetDate = now()->addDay()->toDateString();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->create([
                'target_date' => $targetDate,
            ]);

        $response = $this->actingAs($user)->put(route('reading-plans.update', $readingPlan->id), [
            'target_date' => $newTargetDate,
        ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $newTargetDate,
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $targetDate,
        ]);
    }

    public function test_所有者以外が期日を変更しようとすると403エラーになる(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $targetDate = now()->addDays(3)->toDateString();
        $newTargetDate = now()->addDay()->toDateString();

        $otherReadingPlan = ReadingPlan::factory()
            ->for($otherUser)
            ->create([
                'target_date' => $targetDate,
            ]);

        $response = $this->actingAs($user)->put(route('reading-plans.update', $otherReadingPlan->id), [
            'target_date' => $newTargetDate,
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $otherReadingPlan->id,
            'target_date' => $targetDate,
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $otherReadingPlan->id,
            'target_date' => $newTargetDate,
        ]);
    }

    public function test_未認証ユーザーが読書計画編集画面にアクセスするとログイン画面にリダイレクトされる(): void
    {
        $readingPlan = ReadingPlan::factory()->create();

        $response = $this->get(route('reading-plans.edit', $readingPlan->id));

        $response->assertRedirect(route('login'));
    }

    public function test_未認証ユーザーが期日を変更しようとするとログイン画面へリダイレクトされる(): void
    {
        $targetDate = now()->addDays(3)->toDateString();
        $newTargetDate = now()->addDay()->toDateString();

        $readingPlan = ReadingPlan::factory()
            ->create([
                'target_date' => $targetDate,
            ]);

        $response = $this->put(route('reading-plans.update', $readingPlan->id), [
            'target_date' => $newTargetDate,
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $targetDate,
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $newTargetDate,
        ]);
    }

    public function test_期日未入力で更新するとバリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $targetDate = now()->addDays(3)->toDateString();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->create([
                'target_date' => $targetDate,
            ]);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan->id), [
                'target_date' => '',
            ]);

        $response->assertSessionHasErrors([
            'target_date' => '期日を入力してください。',
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $targetDate,
        ]);
    }

    public function test_期日を過去の日付に変更するとバリデーションメッセージが表示される(): void
    {
        $user = User::factory()->create();

        $targetDate = now()->addDays(3)->toDateString();
        $pastTargetDate = now()->subDays(3)->toDateString();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->create([
                'target_date' => $targetDate,
            ]);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan->id), [
                'target_date' => $pastTargetDate,
            ]);

        $response->assertSessionHasErrors([
            'target_date' => '期日は今日以降の日付を指定してください。',
        ]);

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $targetDate,
        ]);
    }

    public function test_期限切れの読書計画の期日を変更するとステータスが進行中に変更される(): void
    {
        $user = User::factory()->create();

        $oldTargetDate = now()->subDays(3)->toDateString();
        $newTargetDate = now()->addDays(3)->toDateString();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->create([
                'target_date' => $oldTargetDate,
                'status' => ReadingPlanStatus::Expired,
            ]);

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan->id), [
                'target_date' => $newTargetDate,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $newTargetDate,
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $oldTargetDate,
            'status' => ReadingPlanStatus::Expired,
        ]);
    }
}
