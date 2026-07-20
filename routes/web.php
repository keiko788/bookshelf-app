<?php

use App\Http\Controllers\auth\LoginController;
use App\Http\Controllers\BookController;
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
Route::get('/ranking', function () {
    return view('ranking.index');
})->name('ranking.index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');
});

Route::middleware('auth')->group(function () {
    Route::resource('books', BookController::class)->except(['index', 'show']);
    // お気に入り一覧画面
    Route::get('/favorites', function () {
        return view('favorites.index');
    })->name('favorites.index');
    Route::post('/favorites', function () {
        return view('books.show');
    })->name('favorites.toggle');
    // レビュー関連
    Route::post('/reviews', function () {
        return view('books.store');
    })->name('reviews.store');
    Route::post('/reviews/like', function () {
        return view('books.store');
    })->name('reviews.like');
    Route::get('/reviews', function () {
        return view('reviews.edit');
    })->name('reviews.edit');
    Route::delete('/reviews', function () {
        return view('books.show');
    })->name('reviews.destroy');

    // ジャンル一覧画面
    Route::get('/genres', function () {
        return view('favorites.index');
    })->name('genres.index');
});

// 書籍詳細画面
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
