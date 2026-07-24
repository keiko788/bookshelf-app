<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenreStoreRequest;
use App\Http\Requests\GenreUpdateRequest;
use App\Models\Genre;

class GenreController extends Controller
{
    /**
     * ジャンル一覧画面を表示
     */
    public function index()
    {
        $genres = Genre::withCount('books')
            ->latest()
            ->get();

        return view('genres.index', compact('genres'));
    }

    /**
     * ジャンル登録画面を表示
     */
    public function create()
    {
        return view('genres.create');
    }

    /**
     * ジャンルを追加する
     */
    public function store(GenreStoreRequest $request)
    {
        $validated = $request->validated();

        Genre::create($validated);

        return redirect()->route('genres.index')
            ->with('success', 'ジャンルを登録しました');
    }

    /**
     * ジャンル詳細画面を表示
     */
    public function show(Genre $genre)
    {
        $books = $genre->books()
            ->with('genres')
            ->latest()
            ->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    /**
     * ジャンル編集画面を表示
     */
    public function edit(Genre $genre)
    {
        return view('genres.edit', compact('genre'));
    }

    /**
     * ジャンルを更新する
     */
    public function update(GenreUpdateRequest $request, Genre $genre)
    {
        $validated = $request->validated();

        $genre->update($validated);

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを更新しました');
    }

    /**
     * ジャンルを削除する
     */
    public function destroy(Genre $genre)
    {
        if ($genre->books()->exists()) {
            return back()
                ->with('error', '書籍に紐付いているジャンルは削除できません');
        }

        $genre->delete();

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを削除しました');
    }
}
