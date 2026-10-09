<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_通知一覧を表示できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $user->notify(
            new ReadingPlanReminderNotification(
                $readingPlan,
                'three_days_before'
            )
        );

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response
            ->assertOk()
            ->assertViewHas('notifications');
    }

    public function test_通知一覧は1ページ20件で表示される(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        for ($i = 0; $i < 21; $i++) {
            $user->notify(
                new ReadingPlanReminderNotification(
                    $readingPlan,
                    'three_days_before'
                )
            );
        }

        $response = $this->actingAs($user)
            ->get(route('notifications.index'));

        $response
            ->assertOk()
            ->assertViewHas('notifications', function ($notifications) {
                return $notifications->count() === 20
                    && $notifications->total() === 21;
            });
    }

    public function test_自分の通知を既読にできる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $user->notify(
            new ReadingPlanReminderNotification(
                $readingPlan,
                'three_days_before'
            )
        );

        $notification = $user->notifications()->first();

        $response = $this->actingAs($user)
            ->post(route('notifications.read', $notification->id));

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'success',
                '通知を既読にしました。'
            );

        $this->assertNotNull(
            $notification->fresh()->read_at
        );
    }

    public function test_他人の通知は既読にできない(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $otherUser->id,
            'book_id' => $book->id,
            'target_date' => now()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $otherUser->notify(
            new ReadingPlanReminderNotification(
                $readingPlan,
                'three_days_before'
            )
        );

        $notification = $otherUser->notifications()->first();

        $response = $this->actingAs($user)
            ->post(route('notifications.read', $notification->id));

        $response->assertNotFound();

        $this->assertNull(
            $notification->fresh()->read_at
        );
    }
}
