<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class GoogleBooksController extends Controller
{
    /**
     * ISBNから書籍情報を検索する。
     *
     * @param  string  $isbn  ISBN
     * @return JsonResponse 書籍情報
     */
    public function search(string $isbn): JsonResponse
    {
        if (! preg_match('/^\d{13}$/', $isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁の数字で入力してください。',
            ], 422);
        }

        try {
            $response = Http::timeout(10)
                ->retry(3, 100)
                ->get('https://www.googleapis.com/books/v1/volumes', [
                    'q' => "isbn:{$isbn}",
                    'key' => config('services.google_books.key'),
                ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => '書籍情報の取得に失敗しました。',
                ], 502);
            }

            $data = $response->json();

            if (($data['totalItems'] ?? 0) === 0) {
                return response()->json([
                    'error' => '書籍情報が見つかりませんでした。',
                ], 404);
            }

            $volumeInfo = $data['items'][0]['volumeInfo'];
            $authors = implode(', ', $volumeInfo['authors'] ?? []);

            return response()->json([
                'title' => $volumeInfo['title'],
                'author' => $authors,
                'published_date' => $volumeInfo['publishedDate'] ?? '',
                'description' => $volumeInfo['description'] ?? '',
                'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? '',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => '通信エラーが発生しました。',
            ], 500);
        }
    }
}
