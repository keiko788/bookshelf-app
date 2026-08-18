<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookStoreRequest;
use App\Http\Requests\BookUpdateRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧画面を表示する。
     *
     * @param  Request  $request  検索・絞り込み・並び替え条件を含むリクエスト
     * @return View 書籍一覧画面
     */
    public function index(Request $request): View
    {
        $query = Book::with('genres')
            ->withAvg('reviews', 'rating');
        $genres = Genre::all();

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');

            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        $genre = $request->input('genre');

        if ($request->filled('genre')) {
            $query->whereHas('genres', function ($q) use ($genre) {
                $q->where('name', $genre);
            });
        }

        $sort = $request->input('sort', 'newest');

        match ($sort) {
            'newest' => $query->latest(),
            'oldest' => $query->oldest(),
            'title' => $query->orderBy('title'),
            'rating' => $query->orderByDesc('reviews_avg_rating'),
            default => $query->latest(),
        };

        $books = $query
            ->paginate(10)
            ->withQueryString();

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * 書籍登録画面を表示する
     *
     * @return View 書籍登録画面
     */
    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍を登録する。
     *
     * @param  BookStoreRequest  $request  書籍登録用のリクエスト
     * @return RedirectResponse 書籍一覧画面へリダイレクト
     */
    public function store(BookStoreRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        $book = $user->books()->create([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $book->genres()->sync($validated['genres']);

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を登録しました');
    }

    /**
     * 書籍詳細画面を表示する。
     *
     * @param  Book  $book  表示する書籍
     * @return View 書籍詳細画面
     */
    public function show(Book $book): View
    {
        $book->load([
            'genres',
            'reviews.user',
            'reviews.likedByUsers',
        ]);

        if (auth()->check()) {
            auth()->user()->load([
                'favoriteBooks',
                'likedReviews',
            ]);
        }

        return view('books.show', compact('book'));
    }

    /**
     * 書籍編集画面を表示する。
     *
     * @param  Book  $book  編集する書籍
     * @return View 書籍編集画面
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        $book->load('genres');

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍を更新する。
     *
     * @param  BookUpdateRequest  $request  書籍更新用のリクエスト
     * @param  Book  $book  更新する書籍
     * @return RedirectResponse 書籍詳細画面へリダイレクト
     */
    public function update(BookUpdateRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $book->genres()->sync($validated['genres']);

        return redirect()
            ->route('books.show', $book->id)
            ->with('success', '書籍を更新しました');
    }

    /**
     * 書籍を削除する。
     *
     * @param  Book  $book  削除する書籍
     * @return RedirectResponse 書籍一覧画面へリダイレクト
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', '書籍を削除しました');
    }
}
