<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    public function run(): void
    {
        $users = collect([
            User::where('email', 'yamada@example.com')->firstOrFail(),
            User::where('email', 'suzuki@example.com')->firstOrFail(),
            User::where('email', 'tanaka@example.com')->firstOrFail(),
            User::where('email', 'sato@example.com')->firstOrFail(),
            User::where('email', 'takahashi@example.com')->firstOrFail(),
        ]);
        $books = Book::orderBy('id')->get();

        $favorites = [
            0 => [0, 1, 2],
            1 => [2, 3, 4, 5],
            2 => [0, 5, 6, 7, 8],
            3 => [1, 3, 7],
            4 => [4, 8, 9, 10],
        ];

        foreach ($favorites as $userIndex => $bookIndexes) {
            $bookIds = collect($bookIndexes)
                ->map(fn ($bookIndex) => $books[$bookIndex]->id)
                ->all();

            $users[$userIndex]->favoriteBooks()->syncWithoutDetaching($bookIds);
        }
    }
}
