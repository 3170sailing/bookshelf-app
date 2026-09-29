<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_genre(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/genres', [
            'name' => 'ミステリー',
        ]);

        $response
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('success', 'ジャンルを作成しました。');

        $this->assertDatabaseHas('genres', [
            'name' => 'ミステリー',
        ]);
    }

    public function test_genre_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/genres', [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_genre_name_must_not_exceed_50_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/genres', [
            'name' => str_repeat('あ', 51),
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_genre_name_must_be_unique(): void
    {
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)->post('/genres', [
            'name' => '小説',
        ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 1);
    }

    public function test_authenticated_user_can_update_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '更新前ジャンル',
        ]);

        $response = $this->actingAs($user)
            ->put("/genres/{$genre->id}", [
                'name' => '更新後ジャンル',
            ]);

        $response
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('success', 'ジャンルを更新しました。');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新後ジャンル',
        ]);
    }

    public function test_update_allows_genre_to_keep_its_own_name(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $response = $this->actingAs($user)
            ->put("/genres/{$genre->id}", [
                'name' => '小説',
            ]);

        $response->assertSessionDoesntHaveErrors('name');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_genre_update_rejects_another_genres_name(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        Genre::factory()->create([
            'name' => 'ビジネス',
        ]);

        $response = $this->actingAs($user)
            ->put("/genres/{$genre->id}", [
                'name' => 'ビジネス',
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '小説',
        ]);
    }

    public function test_authenticated_user_can_delete_genre_without_books(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)
            ->delete("/genres/{$genre->id}");

        $response
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas('success', 'ジャンルを削除しました。');

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_genre_with_books_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();

        $genre->books()->attach($book->id);

        $response = $this->actingAs($user)
            ->delete("/genres/{$genre->id}");

        $response
            ->assertRedirect(route('genres.index'))
            ->assertSessionHas(
                'error',
                'このジャンルには書籍が紐付いているため削除できません。'
            );

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }
}