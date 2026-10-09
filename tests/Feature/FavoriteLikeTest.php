<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_toggle_book_favorite(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 追加
        $response = $this->actingAs($user)
            ->post("/books/{$book->id}/favorite");

        $response->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // 解除
        $response = $this->actingAs($user)
            ->post("/books/{$book->id}/favorite");

        $response->assertRedirect();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // 再追加
        $response = $this->actingAs($user)
            ->post("/books/{$book->id}/favorite");

        $response->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_guest_cannot_toggle_favorite(): void
    {
        $book = Book::factory()->create();

        $response = $this->post("/books/{$book->id}/favorite");

        $response->assertRedirect('/login');

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_authenticated_user_can_toggle_review_like(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        // 追加
        $response = $this->actingAs($user)
            ->post("/reviews/{$review->id}/like");

        $response->assertRedirect(
            route('books.show', $review->book)
        );

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // 解除
        $response = $this->actingAs($user)
            ->post("/reviews/{$review->id}/like");

        $response->assertRedirect(
            route('books.show', $review->book)
        );

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        // 再追加
        $response = $this->actingAs($user)
            ->post("/reviews/{$review->id}/like");

        $response->assertRedirect(
            route('books.show', $review->book)
        );

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_guest_cannot_toggle_review_like(): void
    {
        $review = Review::factory()->create();

        $response = $this->post("/reviews/{$review->id}/like");

        $response->assertRedirect('/login');

        $this->assertDatabaseCount('review_likes', 0);
    }
}
