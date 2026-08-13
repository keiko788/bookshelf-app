<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenreStoreRequest;
use App\Http\Requests\GenreUpdateRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * ジャンル一覧画面を表示する。
     *
     * @return View ジャンル一覧画面
     */
    public function index(): View
    {
        $genres = Genre::withCount('books')
            ->latest()
            ->get();

        return view('genres.index', compact('genres'));
    }

    /**
     * ジャンル登録画面を表示する。
     *
     * @return View ジャンル登録画面
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * ジャンルを登録する。
     *
     * @param  GenreStoreRequest  $request  ジャンル登録用のリクエスト
     * @return RedirectResponse ジャンル一覧画面へリダイレクト
     */
    public function store(GenreStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Genre::create($validated);

        return redirect()->route('genres.index')
            ->with('success', 'ジャンルを登録しました');
    }

    /**
     * ジャンル詳細画面を表示する。
     *
     * @param  Genre  $genre  表示するジャンル
     * @return View ジャンル詳細画面
     */
    public function show(Genre $genre): View
    {
        $books = $genre->books()
            ->with('genres')
            ->latest()
            ->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    /**
     * ジャンル編集画面を表示する。
     *
     * @param  Genre  $genre  編集するジャンル
     * @return View ジャンル編集画面
     */
    public function edit(Genre $genre): View
    {
        return view('genres.edit', compact('genre'));
    }

    /**
     * ジャンルを更新する。
     *
     * @param  GenreUpdateRequest  $request  ジャンル更新用のリクエスト
     * @param  Genre  $genre  更新するジャンル
     * @return RedirectResponse ジャンル一覧画面へリダイレクト
     */
    public function update(GenreUpdateRequest $request, Genre $genre): RedirectResponse
    {
        $validated = $request->validated();

        $genre->update($validated);

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを更新しました');
    }

    /**
     * ジャンルを削除する。
     *
     * @param  Genre  $genre  削除するジャンル
     * @return RedirectResponse 直前の画面またはジャンル一覧画面へリダイレクト
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        if ($genre->books()->exists()) {
            return back()
                ->with('error', '書籍に紐付いているジャンルは削除できません。');
        }

        $genre->delete();

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを削除しました');
    }
}
