<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use App\Models\Review;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * マイ読書レポート画面を表示する。
     *
     * @return View マイ読書レポート画面
     */
    public function index(): View
    {
        $user = auth()->user();

        $reviews = $user->reviews()
            ->with([
                'book' => function ($query) {
                    $query->withCount('reviews');
                },
                'book.genres',
            ])
            ->get();

        $booksRead = $user->readingPlans()
            ->whereNotNull('completed_at')
            ->distinct()
            ->count('book_id');

        $totalReviews = $reviews->count();

        $averageRating = $reviews->avg('rating');

        $ratingDistribution = collect(range(1, 5))
            ->map(function (int $rating) use ($reviews): int {
                return $reviews
                    ->where('rating', $rating)
                    ->count();
            });

        $topRatedBooks = $reviews
            ->where('rating', '>=', 4)
            ->map(function (Review $review) {
                return [
                    'id' => $review->book->id,
                    'title' => $review->book->title,
                    'author' => $review->book->author,
                    'rating' => $review->rating,
                    'reviews_count' => $review->book->reviews_count,
                ];
            })
            ->sortByDesc('reviews_count')
            ->sortByDesc('rating')
            ->take(5)
            ->values();

        $genreRatings = $reviews->flatMap(function (Review $review) {
            return $review->book->genres
                ->map(function (Genre $genre) use ($review) {
                    return [
                        'id' => $genre->id,
                        'name' => $genre->name,
                        'rating' => $review->rating,
                    ];
                });
        })
            ->groupBy('id')
            ->map(function ($genreReviews) {
                return [
                    'id' => $genreReviews->first()['id'],
                    'name' => $genreReviews->first()['name'],
                    'count' => $genreReviews->count(),
                    'average_rating' => $genreReviews->avg('rating'),
                ];
            })
            ->sortByDesc('count')
            ->sortByDesc('average_rating')
            ->take(5)
            ->values();

        $summary = [
            'total_reviews' => $totalReviews,
            'books_read' => $booksRead,
            'average_rating' => $averageRating,
        ];

        $stats = [
            'summary' => $summary,
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];

        return view('reports.index', compact('stats'));
    }
}
