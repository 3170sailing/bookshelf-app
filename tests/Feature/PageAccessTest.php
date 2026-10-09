<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_public_pages(): void
    {
        $book = Book::factory()->create();

        $this->get('/')->assertOk();
        $this->get('/books')->assertOk();
        $this->get("/books/{$book->id}")->assertOk();
        $this->get('/ranking')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_guest_is_redirected_from_authenticated_pages(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();
        $review = Review::factory()->create();

        $this->get('/books/create')->assertRedirect('/login');
        $this->get("/books/{$book->id}/edit")->assertRedirect('/login');

        $this->get('/genres')->assertRedirect('/login');
        $this->get("/genres/{$genre->id}")->assertRedirect('/login');
        $this->get('/genres/create')->assertRedirect('/login');
        $this->get("/genres/{$genre->id}/edit")->assertRedirect('/login');

        $this->get("/reviews/{$review->id}/edit")->assertRedirect('/login');

        $this->get('/favorites')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_authenticated_pages(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $genre = Genre::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->actingAs($user);

        $this->get('/books/create')->assertOk();
        $this->get("/books/{$book->id}/edit")->assertOk();

        $this->get('/genres')->assertOk();
        $this->get("/genres/{$genre->id}")->assertOk();
        $this->get('/genres/create')->assertOk();
        $this->get("/genres/{$genre->id}/edit")->assertOk();

        $this->get("/reviews/{$review->id}/edit")->assertOk();

        $this->get('/favorites')->assertOk();
    }
}
