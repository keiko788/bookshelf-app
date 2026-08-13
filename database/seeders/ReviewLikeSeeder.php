<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $reviews = Review::all();

        $reviews->each(function ($review) use ($users) {
            $likeCount = random_int(0, 3);

            if ($likeCount === 0) {
                return;
            }

            $likeUsers = $users->filter(function ($user) use ($review) {
                return $user->id !== $review->user_id;
            });

            $likedUserIds = $likeUsers
                ->random($likeCount)
                ->pluck('id');

            $review->LikedByUsers()->syncWithoutDetaching($likedUserIds);
        });
    }
}
