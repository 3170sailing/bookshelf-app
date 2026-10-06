<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use Illuminate\Http\Request;

class ReadingPlanController extends Controller
{
    public function index(Request $request)
    {
        $currentStatus = $request->input('status');

        $query = ReadingPlan::with('book')
            ->where('user_id', auth()->id());

        if ($currentStatus) {
            $query->where('status', $currentStatus);
        }

        $readingPlans = $query
            ->orderBy('target_date')
            ->get();

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    public function create()
    {
        $books = Book::orderBy('title')->get();

        return view('reading-plans.create', compact('books'));
    }

    public function store(StoreReadingPlanRequest $request)
    {
        $validated = $request->validated();

        $exists = ReadingPlan::where('user_id', auth()->id())
            ->where('book_id', $validated['book_id'])
            ->where('status', ReadingPlanStatus::InProgress)
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors([
                    'book_id' => 'この書籍には進行中の読書計画がすでにあります。',
                ]);
        }

        ReadingPlan::create([
            'user_id' => auth()->id(),
            'book_id' => $validated['book_id'],
            'target_date' => $validated['target_date'],
            'status' => ReadingPlanStatus::InProgress,
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を作成しました。');
    }

    public function edit(ReadingPlan $readingPlan)
    {
        $this->authorize('update', $readingPlan);

        return view('reading-plans.edit', compact('readingPlan'));
    }

    public function update(
    UpdateReadingPlanRequest $request,
    ReadingPlan $readingPlan
    ) {
        $this->authorize('update', $readingPlan);

        $validated = $request->validated();

        $readingPlan->update([
            'target_date' => $validated['target_date'],
        ]);

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を更新しました。');
    }

    public function destroy(ReadingPlan $readingPlan)
    {
        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()
            ->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }
    public function complete(ReadingPlan $readingPlan)
{
    $this->authorize('complete', $readingPlan);

    $readingPlan->update([
        'status' => ReadingPlanStatus::Completed,
        'completed_at' => now(),
    ]);

    return redirect()
        ->route('reading-plans.index')
        ->with('success', '読書計画を完了しました。');
}
}