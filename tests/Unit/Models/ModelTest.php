<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_many_reviews(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue(
            $user->reviews->contains($review)
        );
    }

    public function test_user_belongs_to_many_favorite_books(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book->id);

        $this->assertTrue(
            $user->favoriteBooks->contains($book)
        );
    }

    public function test_user_belongs_to_many_liked_reviews(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $user->likedReviews()->attach($review->id);

        $this->assertTrue(
            $user->likedReviews->contains($review)
        );
    }

    public function test_book_belongs_to_many_genres(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $book->genres()->attach($genre->id);

        $this->assertTrue(
            $book->genres->contains($genre)
        );
    }

    public function test_book_has_many_reviews(): void
    {
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'book_id' => $book->id,
        ]);

        $this->assertTrue(
            $book->reviews->contains($review)
        );
    }

    public function test_book_belongs_to_many_favorited_users(): void
    {
        $book = Book::factory()->create();
        $user = User::factory()->create();

        $book->favoritedByUsers()->attach($user->id);

        $this->assertTrue(
            $book->favoritedByUsers->contains($user)
        );
    }

    public function test_genre_belongs_to_many_books(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();

        $genre->books()->attach($book->id);

        $this->assertTrue(
            $genre->books->contains($book)
        );
    }

    public function test_review_belongs_to_user(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue(
            $review->user->is($user)
        );
    }

    public function test_review_belongs_to_book(): void
    {
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'book_id' => $book->id,
        ]);

        $this->assertTrue(
            $review->book->is($book)
        );
    }

    public function test_review_belongs_to_many_liked_users(): void
    {
        $review = Review::factory()->create();
        $user = User::factory()->create();

        $review->likedByUsers()->attach($user->id);

        $this->assertTrue(
            $review->likedByUsers->contains($user)
        );
    }
}