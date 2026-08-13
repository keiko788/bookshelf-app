<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $books = Book::all();
        $user = User::where('email', 'yamada@example.com')->first();

        $scenarioBooks = $books->random(7)->values();

        $readingPlans = [
            [
                'target_date' => Carbon::today()->addDays(3),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
            [
                'target_date' => Carbon::today(),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,

            ],
            [
                'target_date' => Carbon::today()->subDays(3),
                'status' => ReadingPlanStatus::Expired,
                'completed_at' => null,

            ],
            [
                'target_date' => Carbon::today()->subDay(),
                'status' => ReadingPlanStatus::Expired,
                'completed_at' => null,
            ],
            [
                'target_date' => Carbon::today()->addDays(7),
                'status' => ReadingPlanStatus::InProgress,
                'completed_at' => null,
            ],
            [
                'target_date' => Carbon::today(),
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => Carbon::today()->subDay(),
            ],
        ];

        collect($readingPlans)->each(function ($readingPlan, $index) use ($user, $scenarioBooks) {
            ReadingPlan::create([
                'user_id' => $user->id,
                'book_id' => $scenarioBooks[$index]->id,
                'target_date' => $readingPlan['target_date'],
                'status' => $readingPlan['status'],
                'completed_at' => $readingPlan['completed_at'],
            ]);
        });

        $suzuki = User::where('email', 'suzuki@example.com')->first();

        ReadingPlan::create([
            'user_id' => $suzuki->id,
            'book_id' => $scenarioBooks[6]->id,
            'target_date' => Carbon::today()->addDay(),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);
    }
}
