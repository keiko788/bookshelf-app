<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewStoreRequest;
use App\Http\Requests\ReviewUpdateRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * レビューを投稿する。
     *
     * @param  ReviewStoreRequest  $request  レビュー投稿用のリクエスト
     * @param  Book  $book  レビューを投稿する書籍
     * @return RedirectResponse 直前の画面または書籍詳細画面へリダイレクト
     */
    public function store(ReviewStoreRequest $request, Book $book): RedirectResponse
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
            ->route('books.show', $book)
            ->with('success', 'レビューを投稿しました');
    }

    /**
     * レビュー編集画面を表示する。
     *
     * @param  Review  $review  編集するレビュー
     * @return View レビュー編集画面
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        $review->load('book');

        return view('reviews.edit', compact('review'));
    }

    /**
     * レビューを更新する。
     *
     * @param  ReviewUpdateRequest  $request  レビュー更新用のリクエスト
     * @return RedirectResponse 書籍詳細画面へリダイレクト
     */
    public function update(ReviewUpdateRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $validated = $request->validated();

        $review->update($validated);

        $bookId = $review->book_id;

        return redirect()
            ->route('books.show', $bookId)
            ->with('success', 'レビュー更新しました');
    }

    /**
     * レビューを削除する。
     *
     * @param  Review  $review  削除するレビュー
     * @return RedirectResponse 書籍詳細画面へリダイレクト
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $bookId = $review->book_id;

        $review->delete();

        return redirect()
            ->route('books.show', $bookId)
            ->with('success', 'レビューを削除しました');
    }
}
