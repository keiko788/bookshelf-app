<?php

namespace Tests\Feature\ReadingPlan;

use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_所有者は自分の読書計画を削除できる(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()
            ->for($user)
            ->create();

        $response = $this->actingAs($user)->delete(route('reading-plans.destroy', $readingPlan->id));

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    public function test_所有者以外が読書計画を削除すると403エラーになり_読書計画は削除されない(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherReadingPlan = ReadingPlan::factory()
            ->for($otherUser)
            ->create();

        $response = $this->actingAs($user)->delete(route('reading-plans.destroy', $otherReadingPlan->id));

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $otherReadingPlan->id,
        ]);
    }

    public function test_未認証ユーザーが読書計画を削除しようとするとログイン画面にリダイレクトされる(): void
    {
        $readingPlan = ReadingPlan::factory()->create();

        $response = $this->delete(route('reading-plans.destroy', $readingPlan->id));

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }
}
