<?php

namespace Tests\Unit\Enums;

use App\Enums\ReadingPlanStatus;
use PHPUnit\Framework\TestCase;

class ReadingPlanStatusTest extends TestCase
{
    public function test_各ステータスの表示名を取得できる(): void
    {
        $this->assertSame(
            '期限切れ',
            ReadingPlanStatus::Expired->label()
        );

        $this->assertSame(
            '進行中',
            ReadingPlanStatus::InProgress->label()
        );

        $this->assertSame(
            '完了',
            ReadingPlanStatus::Completed->label()
        );
    }

    public function test_各ステータスに応じたバッジの_cs_sクラスを取得できる(): void
    {
        $this->assertSame(
            'bg-red-100',
            ReadingPlanStatus::Expired->badgeClass()
        );

        $this->assertSame(
            'bg-blue-100',
            ReadingPlanStatus::InProgress->badgeClass()
        );

        $this->assertSame(
            'bg-gray-200',
            ReadingPlanStatus::Completed->badgeClass()
        );
    }
}
