<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case Expired = 'expired';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    /**
     * ステータスの表示名を取得する。
     *
     * @return string ステータスの表示名
     */
    public function label(): string
    {
        return match ($this) {
            self::Expired => '期限切れ',
            self::InProgress => '進行中',
            self::Completed => '完了',
        };
    }

    /**
     * ステータスに応じたバッジのCSSクラスを取得する。
     *
     * @return string バッジのCSSクラス
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Expired => 'bg-red-100',
            self::InProgress => 'bg-blue-100',
            self::Completed => 'bg-green-100',
        };
    }
}
