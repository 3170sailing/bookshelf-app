<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    /**
     * 書籍一覧を検索条件付きで取得する。
     */
    public function index(IndexBookRequest $request): AnonymousResourceCollection
    {
        $query = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');

            $query->where(function ($query) use ($keyword): void {
                $query->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('genre_id')) {
            $genreId = $request->input('genre_id');

            $query->whereHas('genres', function ($query) use ($genreId): void {
                $query->where('genres.id', $genreId);
            });
        }

        $books = $query->paginate(
            $request->integer('per_page', 20)
        );

        return BookResource::collection($books);
    }

    public function show(Book $book): BookResource
    {
        $book->load([
            'genres',
            'reviews.user',
        ]);

        $book->loadAvg('reviews', 'rating');
        $book->loadCount('reviews');

        return new BookResource($book);
    }

    /**
     * 認証ユーザーの書籍を登録する。
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $book = DB::transaction(function () use ($request, $validated): Book {
            $book = Book::create([
                'user_id' => $request->user()->id,
                'title' => $validated['title'],
                'author' => $validated['author'],
                'isbn' => $validated['isbn'] ?? null,
                'published_date' => $validated['published_date'] ?? null,
                'description' => $validated['description'] ?? null,
                'image_url' => $validated['image_url'] ?? null,
            ]);

            $book->genres()->sync($validated['genres']);

            return $book;
        });

        $book->load('genres');
        $book->loadAvg('reviews', 'rating');
        $book->loadCount('reviews');

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 所有する書籍情報を更新する。
     */
    public function update(UpdateBookRequest $request, Book $book): BookResource
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

        $book->load('genres');
        $book->loadAvg('reviews', 'rating');
        $book->loadCount('reviews');

        return new BookResource($book);
    }

    /**
     * 所有する書籍を削除する。
     */
    public function destroy(Book $book): Response
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->noContent();
    }
}
