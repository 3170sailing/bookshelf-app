<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
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
        $reviews = Review::orderBy('id')->get();

        foreach ($reviews as $review) {
            $likeCount = rand(0, 3);

            $userIds = $users
                ->where('id', '!=', $review->user_id)
                ->shuffle()
                ->take($likeCount)
                ->pluck('id')
                ->all();

            $review->likedByUsers()->syncWithoutDetaching($userIds);
        }
    }
}
