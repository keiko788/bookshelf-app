<?php

namespace App\Http\Controllers;

use App\Models\Review;

class ReviewLikeController extends Controller
{
    //レビューいいねの登録・解除
    public function toggle(Review $review)
    {
        $user = auth()->user();

        if ($review->user_id === $user->id) {
            return back();
        }

        $review->likedByUsers()->toggle($user->id);

        return back();
    }
}
