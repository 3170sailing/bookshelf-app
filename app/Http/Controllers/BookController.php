<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $query = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');

            $query->where(function ($query) use ($keyword) {
                $query->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('genre')) {
            $query->whereHas('genres', function ($query) use ($request) {
                $query->where('genres.id', $request->input('genre'));
            });
        }

        match ($request->input('sort', 'newest')) {
            'oldest' => $query->oldest(),
            'rating' => $query
                ->orderByRaw('reviews_avg_rating IS NULL')
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count'),
            'title' => $query->orderBy('title'),
            default => $query->latest(),
        };

        $books = $query
            ->paginate(10)
            ->withQueryString();

        $genres = Genre::all();

        return view('books.index', compact('books', 'genres'));
    }

    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $book = DB::transaction(function () use ($validated): Book {
            $book = Book::create([
                'title' => $validated['title'],
                'author' => $validated['author'],
                'isbn' => $validated['isbn'] ?? null,
                'published_date' => $validated['published_date'] ?? null,
                'description' => $validated['description'] ?? null,
                'image_url' => $validated['image_url'] ?? null,
                'user_id' => Auth::id(),
            ]);

            $book->genres()->sync($validated['genres']);

            return $book;
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    public function show(Book $book): View
    {
        $book->load([
            'genres',
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        return view('books.show', compact('book'));
    }

    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $book->load('genres');
        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        DB::transaction(function () use ($book, $validated): void {
            $book->update([
                'title' => $validated['title'],
                'author' => $validated['author'],
                'isbn' => $validated['isbn'] ?? null,
                'published_date' => $validated['published_date'] ?? null,
                'description' => $validated['description'] ?? null,
                'image_url' => $validated['image_url'] ?? null,
            ]);

            $book->genres()->sync($validated['genres']);
        });

        return redirect()
            ->route('books.show', $book)
            ->with('success', '書籍を編集しました。');
    }

    /**
     * ISBNからGoogle Books APIを利用して書籍情報を取得する。
     */
    public function searchByIsbn(string $isbn): JsonResponse
    {
        if (! preg_match('/^\d{13}$/', $isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁で入力してください。',
            ], 400);
        }

        try {
            $apiKey = config('services.google_books.key');

            $query = [
                'q' => 'isbn:'.$isbn,
            ];

            if ($apiKey) {
                $query['key'] = $apiKey;
            }

            $response = Http::get(
                'https://www.googleapis.com/books/v1/volumes',
                $query
            );

            if ($response->status() === 429) {
                return response()->json([
                    'error' => 'Google Books API のクォータを超過しました。.env に GOOGLE_BOOKS_API_KEY を設定してください。',
                ], 429);
            }

            if ($response->failed()) {
                return response()->json([
                    'error' => 'API通信エラーが発生しました。',
                ], 500);
            }

            $data = $response->json();

            if (($data['totalItems'] ?? 0) === 0 || empty($data['items'])) {
                return response()->json([
                    'error' => '書籍が見つかりませんでした。',
                ], 404);
            }

            $volumeInfo = $data['items'][0]['volumeInfo'] ?? [];

            return response()->json([
                'title' => $volumeInfo['title'] ?? '',
                'author' => $volumeInfo['authors'][0] ?? '',
                'published_date' => $volumeInfo['publishedDate'] ?? '',
                'description' => $volumeInfo['description'] ?? '',
                'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? '',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'API通信エラーが発生しました。',
            ], 500);
        }
    }

    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
