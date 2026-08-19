<?php

namespace App\Policies;

use App\Models\ReadingPlan;
use App\Models\User;
use App\Enums\ReadingPlanStatus;
use Illuminate\Auth\Access\Response;

class ReadingPlanPolicy
{
    /**
     * 読書計画登録者本人かつ未完了の場合のみ読了を許可する。
     *
     * @param User $user 読了を行うユーザー
     * @param ReadingPlan $plan 読了対象の読書計画
     * @return bool 読了を許可する場合はtrue
     */
    public function complete(User $user, ReadingPlan $plan): bool
    {
        return $user->id === $plan->user_id
            && $plan->status !== ReadingPlanStatus::Completed;
    }

    /**
     * 読書計画登録者本人かつ未完了の場合のみ更新を許可する。
     *
     * @param  User  $user  更新を行うユーザー
     * @param  ReadingPlan  $plan  更新対象の読書計画
     * @return bool 更新を許可する場合はtrue
     */
    public function update(User $user, ReadingPlan $plan): bool
    {
        return $user->id === $plan->user_id
            && $plan->status !== ReadingPlanStatus::Completed;
    }

    /**
     * 読書計画登録者本人のみ削除を許可する。
     *
     * @param  User  $user  削除を行うユーザー
     * @param  ReadingPlan  $plan  削除対象の読書計画
     * @return bool 削除を許可する場合はtrue
     */
    public function delete(User $user, ReadingPlan $plan): bool
    {
        return $user->id === $plan->user_id;
    }

}
