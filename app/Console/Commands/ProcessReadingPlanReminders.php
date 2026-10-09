<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessReadingPlanReminders extends Command
{
    protected $signature = 'reading-plans:process-reminders';

    protected $description = '読書計画の期限更新とリマインダー通知を処理する';

    public function handle(): int
    {
        $today = Carbon::today();

        $readingPlans = ReadingPlan::with(['user', 'book'])
            ->where('status', '!=', ReadingPlanStatus::Completed->value)
            ->get();

        $readingPlans->each(function (ReadingPlan $readingPlan) use ($today): void {
            $targetDate = Carbon::parse($readingPlan->target_date);

            if ($targetDate->isSameDay($today->copy()->addDays(3))) {
                $readingPlan->user->notify(
                    new ReadingPlanReminderNotification(
                        $readingPlan,
                        'three_days_before'
                    )
                );
            }

            if ($targetDate->isSameDay($today)) {
                $readingPlan->user->notify(
                    new ReadingPlanReminderNotification(
                        $readingPlan,
                        'on_due_date'
                    )
                );
            }

            if ($targetDate->isBefore($today)) {
                $readingPlan->update([
                    'status' => ReadingPlanStatus::Expired,
                ]);
            }

            if ($targetDate->isSameDay($today->copy()->subDays(3))) {
                $readingPlan->user->notify(
                    new ReadingPlanReminderNotification(
                        $readingPlan,
                        'three_days_after'
                    )
                );
            }
        });

        return self::SUCCESS;
    }
}
