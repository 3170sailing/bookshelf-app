<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReadingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $books = Book::take(5)->get();

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $books[0]->id,
            'target_date' => Carbon::today()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $books[1]->id,
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $books[2]->id,
            'target_date' => Carbon::today()->subDays(3),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $books[3]->id,
            'target_date' => Carbon::today()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
        ]);

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $books[4]->id,
            'target_date' => Carbon::today()->subDays(7),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => Carbon::today()->subDays(5),
        ]);
    }
}