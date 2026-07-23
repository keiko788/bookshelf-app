<?php

namespace App\Http\Controllers;

use App\Models\Book;

class FavoriteController extends Controller
{
    // お気に入り一覧画面を表示
    public function index()
    {
        $books = auth()->user()
            ->favoriteBooks()
            ->latest()
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    // お気に入りの登録・解除
    public function toggle(Book $book)
    {
        auth()->user()
            ->favoriteBooks()
            ->toggle($book->id);

        return back();
    }
}
