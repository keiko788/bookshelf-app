<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * ランキング画面を表示する。
     *
     * @return View ランキング一覧画面
     */
    public function index()
    {
        $rankedBooks = Book::withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->has('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->orderBy('id')
            ->take(10)
            ->get();

        return view('ranking.index', compact('rankedBooks'));
    }
}
