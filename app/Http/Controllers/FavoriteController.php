<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * お気に入り一覧画面を表示する。
     *
     * @return View お気に入り一覧画面
     */
    public function index(): View
    {
        $books = auth()->user()
            ->favoriteBooks()
            ->latest()
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * お気に入りを登録・解除する。
     *
     * @param  Book  $book  お気に入り登録・解除する書籍
     * @return RedirectResponse 直前の画面へリダイレクト
     */
    public function toggle(Book $book): RedirectResponse
    {
        auth()->user()
            ->favoriteBooks()
            ->toggle($book->id);

        return back();
    }
}
