<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewStoreRequest;
use App\Http\Requests\ReviewUpdateRequest;
use App\Models\Book;
use App\Models\Review;

class ReviewController extends Controller
{
    // レビューを投稿する
    public function store(ReviewStoreRequest $request, Book $book)
    {
        $user = auth()->user();
        $validated = $request->validated();

        // 同一書籍へのレビュー重複投稿を防止
        $exists = Review::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors([
                    'comment' => 'この書籍には既にレビューを投稿しています',
                ])
                ->withInput();
        }

        Review::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return redirect()
            ->route('books.show')
            ->with('success', 'レビューを投稿しました');
    }

    // レビュー編集画面を表示
    public function edit(Review $review)
    {
        $this->authorize('update', $review);

        $review->load('book');

        return view('reviews.edit', compact('review'));
    }

    // レビューを更新する
    public function update(ReviewUpdateRequest $request, Review $review)
    {
        $this->authorize('update', $review);

        $validated = $request->validated();

        $review->update($validated);

        $bookId = $review->book_id;

        return redirect()
            ->route('books.show', $bookId)
            ->with('success', 'レビュー更新しました');
    }

    // レビューを削除する
    public function destroy(Review $review)
    {
        $this->authorize('delete', $review);

        $bookId = $review->book_id;

        $review->delete();

        return redirect()
            ->route('books.show', $bookId)
            ->with('success', 'レビューを削除しました');
    }
}
