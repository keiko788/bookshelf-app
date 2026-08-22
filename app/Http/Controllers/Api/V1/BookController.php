<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\ApiBookIndexRequest;
use App\Http\Requests\Api\v1\ApiBookStoreRequest;
use App\Http\Requests\Api\v1\ApiBookUpdateRequest;
use App\Http\Resources\Api\V1\BookDetailResource;
use App\Http\Resources\Api\V1\BookIndexResource;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    /**
     * 書籍一覧を取得する。
     *
     * @param  ApiBookIndexRequest  $request  書籍一覧取得用のリクエスト
     * @return AnonymousResourceCollection 書籍一覧のリソースコレクション
     */
    public function index(ApiBookIndexRequest $request): AnonymousResourceCollection
    {
        $query = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');

            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%")
                    ->orWhere('isbn', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }
        if ($request->filled('genre_id')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.id', $request->genre_id);
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        $books = $query
            ->latest()
            ->paginate($perPage);

        return BookIndexResource::collection($books);
    }

    /**
     * 書籍を登録する。
     *
     * @param  ApiBookStoreRequest  $request  書籍登録用のリクエスト
     * @return JsonResponse 登録した書籍情報を含むJSONレスポンス
     */
    public function store(ApiBookStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $book = DB::transaction(function () use ($request, $validated) {
            $book = Book::create([
                'user_id' => $request->user()->id,
                'title' => $validated['title'],
                'author' => $validated['author'],
                'isbn' => $validated['isbn'] ?? null,
                'published_date' => $validated['published_date'] ?? null,
                'description' => $validated['description'] ?? null,
                'image_url' => $validated['image_url'] ?? null,
            ]);

            $book->genres()->sync($validated['genres']);

            return $book;
        });

        $book->load('genres');

        return (new BookResource($book))
            ->additional([
                'message' => '書籍を登録しました。',
            ])
            ->response()
            ->setStatusCode(201);

    }

    /**
     * 書籍詳細を取得する。
     *
     * @param  Book  $book  表示する書籍
     * @return BookDetailResource 書籍詳細リソース
     */
    public function show(Book $book): BookDetailResource
    {
        $book->load([
            'genres',
            'reviews.user',
        ]);

        return new BookDetailResource($book);
    }

    /**
     * 書籍を更新する。
     *
     * @param  ApiBookUpdateRequest  $request  書籍更新用のリクエスト
     * @param  Book  $book  更新する書籍
     * @return BookResource 更新した書籍リソース
     */
    public function update(ApiBookUpdateRequest $request, Book $book): BookResource
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        DB::transaction(function () use ($book, $validated) {
            $book->update([
                'title' => $validated['title'],
                'author' => $validated['author'],
                'isbn' => $validated['isbn'] ?? null,
                'published_date' => $validated['published_date'] ?? null,
                'description' => $validated['description'] ?? null,
                'image_url' => $validated['image_url'] ?? null,
            ]);

            $book->genres()->sync($validated['genres']);
        });

        $book->load('genres');

        return new BookResource($book)
            ->additional([
                'message' => '書籍を更新しました。',
            ]);
    }

    /**
     * 書籍を削除する。
     *
     * @param  Book  $book  削除する書籍
     * @return JsonResponse 空のJSONレスポンス
     */
    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->json(null, 204);
    }
}
