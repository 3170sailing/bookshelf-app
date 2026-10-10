<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_report(): void
    {
        $response = $this->get('/reports');

        $response->assertRedirect('/login');
    }

    public function test_report_displays_only_authenticated_users_statistics(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'user_id' => $otherUser->id,
            'rating' => 1,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/reports');

        $response
            ->assertOk()
            ->assertViewHas('stats', function (array $stats): bool {
                return $stats['summary']['total_reviews'] === 2
                    && $stats['summary']['books_read'] === 2
                    && (float) $stats['summary']['average_rating'] === 4.0;
            });
    }

    public function test_report_calculates_rating_distribution(): void
    {
        $user = User::factory()->create();

        foreach ([5, 5, 3, 1] as $rating) {
            Review::factory()->create([
                'user_id' => $user->id,
                'rating' => $rating,
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->get('/reports');

        $response
            ->assertOk()
            ->assertViewHas('stats', function (array $stats): bool {
                return $stats['rating_distribution']->values()->all()
                    === [1, 0, 1, 0, 2];
            });
    }

    public function test_report_displays_maximum_five_top_rated_books(): void
    {
        $user = User::factory()->create();

        foreach ([5, 5, 5, 4, 4, 4] as $index => $rating) {
            $book = Book::factory()->create([
                'title' => '高評価本'.($index + 1),
            ]);

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $rating,
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->get('/reports');

        $response
            ->assertOk()
            ->assertViewHas('stats', function (array $stats): bool {
                return $stats['top_rated_books']->count() === 5
                    && $stats['top_rated_books']->every(
                        fn (array $book): bool => $book['rating'] >= 4
                    );
            });
    }

    public function test_genre_ranking_prioritizes_review_count_when_average_is_equal(): void
    {
        $user = User::factory()->create();

        $genreWithMoreReviews = Genre::factory()->create([
            'name' => 'レビュー多',
        ]);

        $genreWithFewerReviews = Genre::factory()->create([
            'name' => 'レビュー少',
        ]);

        $ratings = [5, 3];

        foreach ($ratings as $rating) {
            $book = Book::factory()->create();
            $book->genres()->attach($genreWithMoreReviews);

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $rating,
            ]);
        }

        $book = Book::factory()->create();
        $book->genres()->attach($genreWithFewerReviews);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 4,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/reports');

        $response
            ->assertOk()
            ->assertViewHas('stats', function (array $stats): bool {
                return $stats['genre_ratings']->pluck('name')->take(2)->values()->all()
                    === ['レビュー多', 'レビュー少'];
            });
    }

    public function test_genre_ranking_displays_maximum_five_genres(): void
    {
        $user = User::factory()->create();

        for ($i = 1; $i <= 6; $i++) {
            $genre = Genre::factory()->create([
                'name' => "ジャンル{$i}",
            ]);

            $book = Book::factory()->create();
            $book->genres()->attach($genre);

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => 5,
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->get('/reports');

        $response
            ->assertOk()
            ->assertViewHas(
                'stats',
                fn (array $stats): bool => $stats['genre_ratings']->count() === 5
            );
    }
}
