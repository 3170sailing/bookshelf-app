<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranking_displays_books_in_descending_order_of_average_rating(): void
    {
        $highRatedBook = Book::factory()->create([
            'title' => '高評価の本',
        ]);

        $lowRatedBook = Book::factory()->create([
            'title' => '低評価の本',
        ]);

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $highRatedBook->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $lowRatedBook->id,
            'rating' => 2,
        ]);

        Review::factory()->create([
            'book_id' => $lowRatedBook->id,
            'rating' => 3,
        ]);

        $response = $this->get('/ranking');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '高評価の本',
                '低評価の本',
            ]);
    }

    public function test_ranking_prioritizes_review_count_when_average_rating_is_equal(): void
    {
        $manyReviewsBook = Book::factory()->create([
            'title' => 'レビュー件数が多い本',
        ]);

        $fewReviewsBook = Book::factory()->create([
            'title' => 'レビュー件数が少ない本',
        ]);

        Review::factory()->count(3)->create([
            'book_id' => $manyReviewsBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $fewReviewsBook->id,
            'rating' => 5,
        ]);

        $response = $this->get('/ranking');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'レビュー件数が多い本',
                'レビュー件数が少ない本',
            ]);
    }

    public function test_ranking_does_not_display_books_without_reviews(): void
    {
        $bookWithReview = Book::factory()->create([
            'title' => 'レビューありの本',
        ]);

        $bookWithoutReview = Book::factory()->create([
            'title' => 'レビューなしの本',
        ]);

        Review::factory()->create([
            'book_id' => $bookWithReview->id,
            'rating' => 5,
        ]);

        $response = $this->get('/ranking');

        $response
            ->assertOk()
            ->assertSee('レビューありの本')
            ->assertDontSee('レビューなしの本');
    }

    public function test_ranking_displays_maximum_of_ten_books(): void
    {
        $books = Book::factory()->count(11)->create();

        foreach ($books as $book) {
            Review::factory()->create([
                'book_id' => $book->id,
                'rating' => 5,
            ]);
        }

        $response = $this->get('/ranking');

        $response->assertOk();

        $rankedBooks = $response->viewData('rankedBooks');

        $this->assertCount(10, $rankedBooks);
    }

    public function test_ranking_is_accessible_without_authentication(): void
    {
        $response = $this->get('/ranking');

        $response->assertOk();
    }
}
