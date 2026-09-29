<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_book_list_without_authentication(): void
    {
        $book = Book::factory()->create([
            'title' => 'APIテスト書籍',
        ]);

        $genre = Genre::factory()->create([
            'name' => 'テストジャンル',
        ]);

        $book->genres()->attach($genre->id);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        $response = $this->getJson('/api/v1/books');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.title', 'APIテスト書籍')
            ->assertJsonPath('data.0.genres.0.name', 'テストジャンル')
            ->assertJsonPath('data.0.reviews_count', 1);
    }

    public function test_can_get_book_detail_with_reviews(): void
    {
        $book = Book::factory()->create([
            'title' => '詳細APIテスト書籍',
        ]);

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $book->genres()->attach($genre->id);

        $reviewUser = User::factory()->create([
            'name' => 'レビュー太郎',
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $reviewUser->id,
            'rating' => 4,
            'comment' => 'APIテストレビュー',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.title', '詳細APIテスト書籍')
            ->assertJsonPath('data.genres.0.name', '小説')
            ->assertJsonPath('data.reviews.0.user_name', 'レビュー太郎')
            ->assertJsonPath('data.reviews.0.rating', 4)
            ->assertJsonPath('data.reviews.0.comment', 'APIテストレビュー');
    }

    public function test_book_detail_returns_not_found_for_nonexistent_book(): void
    {
        $response = $this->getJson('/api/v1/books/999999');

        $response->assertNotFound();
    }

    public function test_can_create_book_without_authentication(): void
    {
        $genres = Genre::factory()->count(2)->create();

        $response = $this->postJson('/api/v1/books', [
            'title' => 'API新規書籍',
            'author' => 'API著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-09-29',
            'description' => 'APIから登録した書籍です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => $genres->pluck('id')->all(),
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'API新規書籍')
            ->assertJsonPath('data.author', 'API著者');

        $this->assertDatabaseHas('books', [
            'title' => 'API新規書籍',
            'isbn' => '1234567890123',
        ]);

        $book = Book::where('isbn', '1234567890123')->first();

        $this->assertCount(2, $book->genres);
    }

    public function test_book_creation_returns_validation_errors(): void
    {
        $response = $this->postJson('/api/v1/books', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'author',
                'isbn',
                'published_date',
                'genres',
            ]);
    }

    public function test_can_update_book_without_authentication(): void
    {
        $book = Book::factory()->create([
            'isbn' => '1234567890123',
        ]);

        $oldGenre = Genre::factory()->create();
        $newGenre = Genre::factory()->create();

        $book->genres()->attach($oldGenre->id);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => 'API更新後書籍',
            'author' => '更新後著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-09-29',
            'description' => '更新しました。',
            'image_url' => 'https://example.com/updated.jpg',
            'genres' => [$newGenre->id],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.title', 'API更新後書籍');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => 'API更新後書籍',
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $oldGenre->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $newGenre->id,
        ]);
    }

    public function test_update_allows_book_to_keep_its_own_isbn(): void
    {
        $book = Book::factory()->create([
            'isbn' => '1234567890123',
        ]);

        $genre = Genre::factory()->create();

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => $book->title,
            'author' => $book->author,
            'isbn' => '1234567890123',
            'published_date' => $book->published_date,
            'description' => $book->description,
            'image_url' => $book->image_url,
            'genres' => [$genre->id],
        ]);

        $response
            ->assertOk()
            ->assertJsonMissingValidationErrors('isbn');
    }

    public function test_book_update_returns_not_found_for_nonexistent_book(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->putJson('/api/v1/books/999999', [
            'title' => '存在しない書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-09-29',
            'genres' => [$genre->id],
        ]);

        $response->assertNotFound();
    }

    public function test_can_delete_book_and_related_data_without_authentication(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();
        $favoriteUser = User::factory()->create();

        $book->genres()->attach($genre->id);

        Review::factory()->create([
            'book_id' => $book->id,
        ]);

        $favoriteUser->favoriteBooks()->attach($book->id);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
        ]);
    }

    public function test_book_delete_returns_not_found_for_nonexistent_book(): void
    {
        $response = $this->deleteJson('/api/v1/books/999999');

        $response->assertNotFound();
    }
}