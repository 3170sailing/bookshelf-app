<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private ReadingPlan $readingPlan,
        private string $timing
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reading_plan_id' => $this->readingPlan->id,
            'book_id' => $this->readingPlan->book_id,
            'timing' => $this->timing,
            'title' => $this->title(),
            'body' => $this->body(),
        ];
    }

    private function title(): string
    {
        return match ($this->timing) {
            'three_days_before' => '読書期日の3日前です',
            'on_due_date' => '読書期日は今日です',
            'three_days_after' => '読書期日を過ぎています',
            default => '読書計画のお知らせ',
        };
    }

    private function body(): string
    {
        return match ($this->timing) {
            'three_days_before' => "「{$this->readingPlan->book->title}」の読書期日まであと3日です。",
            'on_due_date' => "「{$this->readingPlan->book->title}」の読書期日は今日です。",
            'three_days_after' => "「{$this->readingPlan->book->title}」の読書期日から3日経過しました。",
            default => "「{$this->readingPlan->book->title}」の読書計画を確認してください。",
        };
    }
}