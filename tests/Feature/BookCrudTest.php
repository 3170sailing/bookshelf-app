<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_book_with_genres(): void
    {
        $user = User::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-09-29',
            'description' => 'テスト説明',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => $genres->pluck('id')->all(),
        ]);

        $book = Book::where('isbn', '1234567890123')->first();

        $this->assertNotNull($book);
        $this->assertSame($user->id, $book->user_id);
        $this->assertCount(2, $book->genres);

        $response
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', '書籍を登録しました。');
    }

    public function test_book_creation_fails_when_required_fields_are_missing(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/books/create')
            ->post('/books', []);

        $response
            ->assertRedirect('/books/create')
            ->assertSessionHasErrors([
                'title',
                'author',
                'genres',
            ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_book_creation_validates_isbn_image_url_and_genres(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '123',
            'published_date' => '2026-09-29',
            'description' => null,
            'image_url' => 'not-a-url',
            'genres' => [],
        ]);

        $response->assertSessionHasErrors([
            'isbn',
            'image_url',
            'genres',
        ]);
    }

    public function test_isbn_must_be_unique_when_creating_book(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        Book::factory()->create([
            'isbn' => '1234567890123',
        ]);

        $response = $this->actingAs($user)->post('/books', [
            'title' => '別の書籍',
            'author' => '別の著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-09-29',
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertSessionHasErrors('isbn');
    }

    public function test_owner_can_update_book_and_genres(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '1234567890123',
        ]);

        $oldGenre = Genre::factory()->create();
        $newGenre = Genre::factory()->create();

        $book->genres()->attach($oldGenre->id);

        $response = $this->actingAs($user)->put("/books/{$book->id}", [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-09-30',
            'description' => '更新後説明',
            'image_url' => 'https://example.com/updated.jpg',
            'genres' => [$newGenre->id],
        ]);

        $response
            ->assertRedirect(route('books.show', $book))
            ->assertSessionHas('success', '書籍を編集しました。');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '1234567890123',
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
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '1234567890123',
        ]);

        $response = $this->actingAs($user)->put("/books/{$book->id}", [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-09-29',
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertSessionDoesntHaveErrors('isbn');
    }

    public function test_other_user_cannot_update_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)->put("/books/{$book->id}", [
            'title' => '不正な更新',
            'author' => 'テスト著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-09-29',
            'description' => null,
            'image_url' => null,
            'genres' => [$genre->id],
        ]);

        $response->assertForbidden();
    }

    public function test_owner_can_delete_book_and_related_data(): void
    {
        $user = User::factory()->create();
        $favoriteUser = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $book->genres()->attach($genre->id);

        Review::factory()->create([
            'book_id' => $book->id,
        ]);

        $favoriteUser->favoriteBooks()->attach($book->id);

        $response = $this->actingAs($user)
            ->delete("/books/{$book->id}");

        $response
            ->assertRedirect(route('books.index'))
            ->assertSessionHas('success', '書籍を削除しました。');

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

    public function test_other_user_cannot_delete_book(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->delete("/books/{$book->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }
}
