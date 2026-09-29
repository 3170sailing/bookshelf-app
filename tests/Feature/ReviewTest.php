<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post("/books/{$book->id}/reviews", [
                'rating' => 5,
                'comment' => 'とても面白い本でした。',
            ]);

        $response
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', 'レビューを投稿しました。');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);
    }

    public function test_review_creation_validates_rating(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post("/books/{$book->id}/reviews", [
                'rating' => 6,
                'comment' => 'テストレビュー',
            ]);

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_creation_requires_rating(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post("/books/{$book->id}/reviews", [
                'comment' => 'テストレビュー',
            ]);

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_owner_can_update_review(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '更新前コメント',
        ]);

        $response = $this->actingAs($user)
            ->put("/reviews/{$review->id}", [
                'rating' => 5,
                'comment' => '更新後コメント',
            ]);

        $response
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', 'レビューを更新しました。');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => '更新後コメント',
        ]);
    }

    public function test_review_update_validates_rating(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($user)
            ->put("/reviews/{$review->id}", [
                'rating' => 0,
                'comment' => '不正な評価値',
            ]);

        $response->assertSessionHasErrors('rating');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
        ]);
    }

    public function test_other_user_cannot_update_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->put("/reviews/{$review->id}", [
                'rating' => 5,
                'comment' => '不正な更新',
            ]);

        $response->assertForbidden();
    }

    public function test_owner_can_delete_review_and_related_likes(): void
    {
        $user = User::factory()->create();
        $likedUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $review->likedByUsers()->attach($likedUser->id);

        $response = $this->actingAs($user)
            ->delete("/reviews/{$review->id}");

        $response
            ->assertRedirect(route('books.show', $review->book))
            ->assertSessionHas('success', 'レビューを削除しました。');

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'review_id' => $review->id,
        ]);
    }

    public function test_other_user_cannot_delete_review(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->delete("/reviews/{$review->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }
}