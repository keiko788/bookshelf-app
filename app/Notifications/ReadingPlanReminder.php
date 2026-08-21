<?php

namespace App\Notifications;

use App\Enums\ReadingPlanReminderTiming;
use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminder extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        protected ReadingPlan $readingPlan,
        protected ReadingPlanReminderTiming $timing
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * データベース通知に保存する内容を定義する。
     *
     * @param  object  $notifiable  通知対象
     * @return array<string, string> 通知内容
     */
    public function toDatabase(object $notifiable): array
    {
        $title = match ($this->timing) {
            ReadingPlanReminderTiming::ThreeDaysBefore => '読書期限が近づいています',
            ReadingPlanReminderTiming::OnDueDate => '本日が読書期限です',
            ReadingPlanReminderTiming::ThreeDaysAfter => '読書期限を過ぎています',
        };

        $body = match ($this->timing) {
            ReadingPlanReminderTiming::ThreeDaysBefore => "「{$this->readingPlan->book->title}」の読書期限まであと3日です。",
            ReadingPlanReminderTiming::OnDueDate => "「{$this->readingPlan->book->title}」は本日が読書期限です。",
            ReadingPlanReminderTiming::ThreeDaysAfter => "「{$this->readingPlan->book->title}」の読書期限を3日過ぎています。",
        };

        return [
            'timing' => $this->timing->value,
            'title' => $title,
            'body' => $body,
        ];
    }
}
