<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookIsbnSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_isbnから書籍情報を取得できる(): void
    {
        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'Laravel入門',
                            'authors' => ['山田太郎'],
                            'publishedDate' => '2020-01-01',
                            'description' => 'Laravelの入門書です。',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/book.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/books/isbn/9781234567890');

        $response
            ->assertStatus(200)
            ->assertJson([
                'title' => 'Laravel入門',
                'author' => '山田太郎',
                'published_date' => '2020-01-01',
                'description' => 'Laravelの入門書です。',
                'image_url' => 'https://example.com/book.jpg',
            ]);
    }

    public function test_isbnが13桁でない場合は400を返す(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/books/isbn/123456789012');

        $response
            ->assertStatus(400)
            ->assertJson([
                'error' => 'ISBNは13桁で入力してください。',
            ]);
    }

    public function test_書籍が見つからない場合は404を返す(): void
    {
        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/books/isbn/9781234567890');

        $response
            ->assertStatus(404)
            ->assertJson([
                'error' => '書籍が見つかりませんでした。',
            ]);
    }

    public function test_apiのクォータ超過の場合は429を返す(): void
    {
        Http::fake([
            'www.googleapis.com/*' => Http::response([], 429),
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/books/isbn/9781234567890');

        $response
            ->assertStatus(429)
            ->assertJson([
                'error' => 'Google Books API のクォータを超過しました。.env に GOOGLE_BOOKS_API_KEY を設定してください。',
            ]);
    }

    public function test_api通信エラーの場合は500を返す(): void
    {
        Http::fake([
            'www.googleapis.com/*' => Http::response([], 503),
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->getJson('/books/isbn/9781234567890');

        $response
            ->assertStatus(500)
            ->assertJson([
                'error' => 'API通信エラーが発生しました。',
            ]);
    }
}
