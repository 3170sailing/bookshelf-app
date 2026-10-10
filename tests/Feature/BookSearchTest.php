<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_books_can_be_searched_by_title_keyword(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'PHP実践',
            'author' => '佐藤花子',
        ]);

        $response = $this->get('/books?keyword=Laravel');

        $response
            ->assertOk()
            ->assertSee('Laravel入門')
            ->assertDontSee('PHP実践');
    }

    public function test_books_can_be_searched_by_author_keyword(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'PHP実践',
            'author' => '佐藤花子',
        ]);

        $response = $this->get('/books?keyword=佐藤');

        $response
            ->assertOk()
            ->assertSee('PHP実践')
            ->assertDontSee('Laravel入門');
    }

    public function test_books_can_be_filtered_by_genre(): void
    {
        $novel = Genre::factory()->create(['name' => '小説']);
        $business = Genre::factory()->create(['name' => 'ビジネス']);

        $novelBook = Book::factory()->create(['title' => '小説テスト']);
        $businessBook = Book::factory()->create(['title' => 'ビジネステスト']);

        $novelBook->genres()->attach($novel);
        $businessBook->genres()->attach($business);

        $response = $this->get("/books?genre={$novel->id}");

        $response
            ->assertOk()
            ->assertSee('小説テスト')
            ->assertDontSee('ビジネステスト');
    }

    public function test_no_search_results_displays_message(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $response = $this->get('/books?keyword=存在しない書籍');

        $response
            ->assertOk()
            ->assertSee('書籍が見つかりませんでした。');
    }

    public function test_books_can_be_sorted_by_oldest(): void
    {
        Book::factory()->create([
            'title' => '新しい本',
            'created_at' => now(),
        ]);

        Book::factory()->create([
            'title' => '古い本',
            'created_at' => now()->subDay(),
        ]);

        $response = $this->get('/books?sort=oldest');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '古い本',
                '新しい本',
            ]);
    }

    public function test_books_can_be_sorted_by_title(): void
    {
        Book::factory()->create(['title' => 'BBB']);
        Book::factory()->create(['title' => 'AAA']);

        $response = $this->get('/books?sort=title');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'AAA',
                'BBB',
            ]);
    }

    public function test_rating_sort_prioritizes_review_count_when_average_is_equal(): void
    {
        $user = User::factory()->create();

        $bookWithMoreReviews = Book::factory()->create([
            'title' => 'レビュー数が多い本',
        ]);

        $bookWithFewerReviews = Book::factory()->create([
            'title' => 'レビュー数が少ない本',
        ]);

        Review::factory()->create([
            'book_id' => $bookWithMoreReviews->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $bookWithMoreReviews->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'book_id' => $bookWithFewerReviews->id,
            'rating' => 4,
        ]);

        $response = $this->get('/books?sort=rating');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'レビュー数が多い本',
                'レビュー数が少ない本',
            ]);
    }
}
