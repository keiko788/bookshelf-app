<?php

use App\Http\Controllers\auth\LoginController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\GoogleBooksController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReviewLikeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('books.index');
});

// 書籍一覧画面
Route::get('/books', [BookController::class, 'index'])->name('books.index');

// ランキング画面
Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');
});

Route::middleware('auth')->group(function () {
    Route::resource('books', BookController::class)->except(['index', 'show']);

    Route::get('/books/isbn/{isbn}', [GoogleBooksController::class, 'search']);

    // お気に入り
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    // レビュー関連
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::post('/reviews/{review}/like', [ReviewLikeController::class, 'toggle'])->name('reviews.like');

    // ジャンル関連
    Route::resource('genres', GenreController::class);

    // マイ読書レポート関連
    Route::get('/reports', function () {
        return view('reports.index');
    })->name('reports.index');

    // 読書計画関連
    Route::get('/reading-plans', [ReadingPlanController::class, 'index'])
        ->name('reading-plans.index');

    Route::get('/reading-plans/create', [ReadingPlanController::class, 'create'])
        ->name('reading-plans.create');

    Route::post('/reading-plans', [ReadingPlanController::class, 'store'])
        ->name('reading-plans.store');

    Route::get('/reading-plans/{plan}/edit', [ReadingPlanController::class, 'edit'])
        ->name('reading-plans.edit');

    Route::put('/reading-plans/{plan}', [ReadingPlanController::class, 'update'])
        ->name('reading-plans.update');

    Route::delete('/reading-plans/{plan}', [ReadingPlanController::class, 'destroy'])
        ->name('reading-plans.destroy');

    Route::post('/reading-plans/{plan}/complete', [ReadingPlanController::class, 'complete'])
        ->name('reading-plans.complete');

    // 通知一覧
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
});

// 書籍詳細画面
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
