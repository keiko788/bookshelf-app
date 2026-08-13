<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\RedirectResponse;

class ReviewLikeController extends Controller
{
    /**
     * レビューいいねを登録・解除する。
     *
     * @param  Review  $review  いいねを登録・解除するレビュー
     * @return RedirectResponse 直前の画面へリダイレクト
     */
    public function toggle(Review $review): RedirectResponse
    {
        $user = auth()->user();

        if ($review->user_id === $user->id) {
            return back();
        }

        $review->likedByUsers()->toggle($user->id);

        return back();
    }
}
