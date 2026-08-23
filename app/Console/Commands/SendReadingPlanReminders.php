<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanReminderTiming;
use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminder;
use Illuminate\Console\Command;

class SendReadingPlanReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reading-plans:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '読書計画の期限に応じてリマインダー通知を送信する。';

    /**
     * 未通知の場合に読書計画のリマインダー通知を送信する。
     *
     * @param  ReadingPlan  $readingPlan  通知対象の読書計画
     * @param  ReadingPlanReminderTiming  $timing  通知を送るタイミング
     */
    private function sendReminder(
        ReadingPlan $readingPlan,
        ReadingPlanReminderTiming $timing
    ): void {
        $alreadyNotified = $readingPlan->user
            ->notifications()
            ->where('data->reading_plan_id', $readingPlan->id)
            ->where('data->timing', $timing->value)
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        $readingPlan->user->notify(
            new ReadingPlanReminder($readingPlan, $timing)
        );
    }

    /**
     * 読書計画の期限に応じてリマインダー通知を送信する。
     *
     * @return int コマンドの終了コード
     */
    public function handle(): int
    {
        $threeDaysBeforePlans = ReadingPlan::where(
            'status',
            '!=',
            ReadingPlanStatus::Completed->value
        )
            ->whereDate('target_date', now()->addDays(3))
            ->get();

        $threeDaysBeforePlans->each(function (ReadingPlan $readingPlan): void {
            $this->sendReminder(
                $readingPlan,
                ReadingPlanReminderTiming::ThreeDaysBefore
            );
        });

        $onDueDatePlans = ReadingPlan::where(
            'status',
            '!=',
            ReadingPlanStatus::Completed->value
        )
            ->whereDate('target_date', today())
            ->get();

        $onDueDatePlans->each(function (ReadingPlan $readingPlan): void {
            $this->sendReminder(
                $readingPlan,
                ReadingPlanReminderTiming::OnDueDate
            );
        });

        $threeDaysAfterPlans = ReadingPlan::where(
            'status',
            '!=',
            ReadingPlanStatus::Completed->value
        )
            ->whereDate('target_date', now()->subDays(3))
            ->get();

        $threeDaysAfterPlans->each(function (ReadingPlan $readingPlan): void {
            $this->sendReminder(
                $readingPlan,
                ReadingPlanReminderTiming::ThreeDaysAfter
            );
        });

        return Command::SUCCESS;
    }
}
