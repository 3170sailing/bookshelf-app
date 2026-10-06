<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_読書計画を作成できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => now()->addWeek()->format('Y-m-d'),
        ]);

        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success', '読書計画を作成しました。');

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::InProgress->value,
        ]);
    }

    public function test_書籍を選択しないと読書計画を作成できない(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('reading-plans.create'))
            ->post(route('reading-plans.store'), [
                'target_date' => now()->addWeek()->format('Y-m-d'),
            ]);

        $response
            ->assertRedirect(route('reading-plans.create'))
            ->assertSessionHasErrors([
                'book_id' => '書籍を選択してください。',
            ]);
    }

    public function test_存在しない書籍では読書計画を作成できない(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => 999999,
            'target_date' => now()->addWeek()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors([
            'book_id' => '選択された書籍は存在しません。',
        ]);
    }

    public function test_期日が未入力の場合は読書計画を作成できない(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
        ]);

        $response->assertSessionHasErrors([
            'target_date' => '期日は必須です。',
        ]);
    }

    public function test_期日が有効な日付形式でない場合は読書計画を作成できない(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => 'invalid-date',
        ]);

        $response->assertSessionHasErrors([
            'target_date' => '期日は有効な日付形式で入力してください。',
        ]);
    }

    public function test_過去の日付では読書計画を作成できない(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(route('reading-plans.store'), [
            'book_id' => $book->id,
            'target_date' => now()->subDay()->format('Y-m-d'),
        ]);

        $response->assertSessionHasErrors('target_date');

        $this->assertDatabaseMissing('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_自分の読書計画を更新できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $newTargetDate = now()->addWeeks(2)->format('Y-m-d');

        $response = $this->actingAs($user)->put(
            route('reading-plans.update', $readingPlan),
            [
                'target_date' => $newTargetDate,
            ]
        );

        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success', '読書計画を更新しました。');

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
            'target_date' => $newTargetDate,
        ]);
    }

    public function test_他人の読書計画は更新できない(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($otherUser)->put(
            route('reading-plans.update', $readingPlan),
            [
                'target_date' => now()->addWeeks(2)->format('Y-m-d'),
            ]
        );

        $response->assertForbidden();
    }

    public function test_自分の読書計画を削除できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success', '読書計画を削除しました。');

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    public function test_他人の読書計画は削除できない(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($otherUser)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertForbidden();
    }

    public function test_読書計画を完了できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response
            ->assertRedirect(route('reading-plans.index'))
            ->assertSessionHas('success', '読書計画を完了しました。');

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $readingPlan->status
        );

        $this->assertNotNull($readingPlan->completed_at);
    }

    public function test_他人の読書計画は完了できない(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'target_date' => now()->addWeek(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        $response = $this->actingAs($otherUser)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertForbidden();

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::InProgress,
            $readingPlan->status
        );

        $this->assertNull($readingPlan->completed_at);
    }
}