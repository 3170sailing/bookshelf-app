<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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

    public function test_book_list_paginates_twenty_books_per_page(): void
    {
        Book::factory()->count(21)->create();

        $response = $this->getJson('/api/v1/books');

        $response
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.total', 21);
    }

    public function test_book_list_can_search_by_keyword(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'PHP実践',
            'author' => '佐藤花子',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=Laravel');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel入門');
    }

    public function test_book_list_can_search_by_author_keyword(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'PHP実践',
            'author' => '佐藤花子',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=佐藤');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'PHP実践')
            ->assertJsonPath('data.0.author', '佐藤花子');
    }

    public function test_book_list_can_filter_by_genre(): void
    {
        $novelGenre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $businessGenre = Genre::factory()->create([
            'name' => 'ビジネス',
        ]);

        $novelBook = Book::factory()->create([
            'title' => '小説テスト',
        ]);

        $businessBook = Book::factory()->create([
            'title' => 'ビジネステスト',
        ]);

        $novelBook->genres()->attach($novelGenre->id);
        $businessBook->genres()->attach($businessGenre->id);

        $response = $this->getJson("/api/v1/books?genre_id={$novelGenre->id}");

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '小説テスト');
    }

    public function test_book_list_uses_requested_per_page(): void
    {
        Book::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/books?per_page=2');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2);
    }

    public function test_book_list_validates_search_parameters(): void
    {
        $response = $this->getJson(
            '/api/v1/books?genre_id=999999&page=0&per_page=0'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'genre_id',
                'page',
                'per_page',
            ]);
    }

    public function test_book_list_returns_average_rating_rounded_to_one_decimal_place(): void
    {
        $book = Book::factory()->create();

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 3,
        ]);

        $response = $this->getJson('/api/v1/books');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.average_rating', 3.3);
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

    public function test_cannot_create_book_without_authentication(): void
    {
        $response = $this->postJson('/api/v1/books', []);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_book(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', [
            'title' => 'API新規書籍',
            'author' => 'API著者',
            'description' => 'APIから登録した書籍です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => $genres->pluck('id')->all(),
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'API新規書籍')
            ->assertJsonPath('data.author', 'API著者');

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'API新規書籍',
            'isbn' => null,
            'published_date' => null,
        ]);

        $book = Book::where('title', 'API新規書籍')->firstOrFail();

        $this->assertCount(2, $book->genres);
    }

    public function test_authenticated_book_creation_returns_validation_errors(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/books', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'author',
                'genres',
            ])
            ->assertJsonMissingValidationErrors([
                'isbn',
                'published_date',
            ]);
    }

    public function test_cannot_update_book_without_authentication(): void
    {
        $book = Book::factory()->create();

        $response = $this->putJson("/api/v1/books/{$book->id}", []);

        $response->assertUnauthorized();
    }

    public function test_authenticated_owner_can_update_book(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '1234567890123',
        ]);

        $oldGenre = Genre::factory()->create();
        $newGenre = Genre::factory()->create();

        $book->genres()->attach($oldGenre->id);

        Sanctum::actingAs($user);

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
            'user_id' => $user->id,
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

    public function test_authenticated_non_owner_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $genre = Genre::factory()->create();

        Sanctum::actingAs($otherUser);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '他人による更新',
            'author' => 'テスト著者',
            'genres' => [$genre->id],
        ]);

        $response->assertForbidden();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
            'title' => '他人による更新',
        ]);
    }

    public function test_update_allows_book_to_keep_its_own_isbn(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '1234567890123',
        ]);

        $genre = Genre::factory()->create();

        Sanctum::actingAs($user);

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
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->putJson('/api/v1/books/999999', [
            'title' => '存在しない書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-09-29',
            'genres' => [$genre->id],
        ]);

        $response->assertNotFound();
    }

    public function test_cannot_delete_book_without_authentication(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertUnauthorized();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_authenticated_owner_can_delete_book_and_related_data(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::factory()->create();
        $favoriteUser = User::factory()->create();

        $book->genres()->attach($genre->id);

        Review::factory()->create([
            'book_id' => $book->id,
        ]);

        $favoriteUser->favoriteBooks()->attach($book->id);

        Sanctum::actingAs($user);

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

    public function test_authenticated_non_owner_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_book_delete_returns_not_found_for_nonexistent_book(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/v1/books/999999');

        $response->assertNotFound();
    }
}
