<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReadingPlanReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_期日の3日前にリマインダー通知が送信される(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-08 10:00:00'));

        Notification::fake();

        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => Carbon::today()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        Artisan::call('reading-plans:process-reminders');

        Notification::assertSentTo(
            $user,
            ReadingPlanReminderNotification::class,
            function (ReadingPlanReminderNotification $notification) use ($user): bool {
                return $notification->toArray($user)['timing'] === 'three_days_before';
            }
        );

        Carbon::setTestNow();
    }

    public function test_期日当日にリマインダー通知が送信される(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-08 10:00:00'));

        Notification::fake();

        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        Artisan::call('reading-plans:process-reminders');

        Notification::assertSentTo(
            $user,
            ReadingPlanReminderNotification::class,
            function (ReadingPlanReminderNotification $notification) use ($user): bool {
                return $notification->toArray($user)['timing'] === 'on_due_date';
            }
        );

        Carbon::setTestNow();
    }

    public function test_期日から3日後に期限切れとなりリマインダー通知が送信される(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-08 10:00:00'));

        Notification::fake();

        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => Carbon::today()->subDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        Artisan::call('reading-plans:process-reminders');

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $readingPlan->status
        );

        Notification::assertSentTo(
            $user,
            ReadingPlanReminderNotification::class,
            function (ReadingPlanReminderNotification $notification) use ($user): bool {
                return $notification->toArray($user)['timing'] === 'three_days_after';
            }
        );

        Carbon::setTestNow();
    }

    public function test_通知対象外の日付ではリマインダー通知が送信されない(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-08 10:00:00'));

        Notification::fake();

        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => Carbon::today()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        Artisan::call('reading-plans:process-reminders');

        Notification::assertNothingSent();

        Carbon::setTestNow();
    }

    public function test_読了済みの読書計画はリマインダー処理の対象にならない(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-08 10:00:00'));

        Notification::fake();

        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => Carbon::today()->subDays(3),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => Carbon::today()->subDays(5),
        ]);

        Artisan::call('reading-plans:process-reminders');

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $readingPlan->status
        );

        Notification::assertNothingSent();

        Carbon::setTestNow();
    }

    public function test_期日を過ぎた進行中の読書計画は期限切れになる(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-08 10:00:00'));

        Notification::fake();

        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => Carbon::today()->subDay(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        Artisan::call('reading-plans:process-reminders');

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $readingPlan->status
        );

        Notification::assertNothingSent();

        Carbon::setTestNow();
    }

    public function test_期日前の進行中の読書計画は進行中のままになる(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-08 10:00:00'));

        Notification::fake();

        $user = User::factory()->create();
        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => Carbon::today()->addDays(5),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        Artisan::call('reading-plans:process-reminders');

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $readingPlan->status
        );

        Notification::assertNothingSent();

        Carbon::setTestNow();
    }
}
