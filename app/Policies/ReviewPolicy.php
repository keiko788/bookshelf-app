<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * レビュー投稿者本人のみ更新を許可する。
     *
     * @param  User  $user  更新を行うユーザー
     * @param  Review  $review  更新対象のレビュー
     * @return bool 更新を許可する場合はtrue
     */
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    /**
     * レビュー投稿者本人のみ削除を許可する。
     *
     * @param  User  $user  削除を行うユーザー
     * @param  Review  $review  削除対象のレビュー
     * @return bool 削除を許可する場合はtrue
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }
}
