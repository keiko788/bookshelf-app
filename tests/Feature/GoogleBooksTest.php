<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleBooksTest extends TestCase
{
    use RefreshDatabase;

    public function test_isbn13桁で検索すると書籍情報を取得できる(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'Laravel入門',
                            'authors' => ['山田太郎'],
                            'publishedDate' => '2026-01-01',
                            'description' => 'Laravelの入門書です。',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/image.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->getJson('/books/isbn/9781234567890');

        $response->assertOk();
        $response->assertJson([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'published_date' => '2026-01-01',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/image.jpg',
        ]);
    }

    public function test_isbnが13桁でない値で検索する(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/books/isbn/978123456789');

        $response->assertStatus(422);
        $response->assertJsonPath(
            'error',
            'ISBNは13桁の数字で入力してください。',
        );

        Http::assertNothingSent();
    }

    public function test_該当書籍がない場合_404を返す(): void
    {
        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'totalItems' => 0,
                'items' => [],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/books/isbn/9781234567890');

        $response->assertStatus(404);
        $response->assertJsonPath(
            'error',
            '書籍情報が見つかりませんでした。',
        );

        Http::assertSentCount(1);
    }

    public function test_google_books_ap_iがエラーレスポンスを返した場合_502を返す(): void
    {
        Http::fake([
            'www.googleapis.com/*' => Http::response([], 500),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/books/isbn/9781234567890');

        $response->assertStatus(502);
        $response->assertJsonPath(
            'error',
            '書籍情報の取得に失敗しました。',
        );

        Http::assertSentCount(3);
    }

    public function test_google_books_ap_iとの通信で例外が発生した場合_500を返す(): void
    {
        Http::fake(function () {
            throw new ConnectionException('通信エラー');
        });

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/books/isbn/9781234567890');

        $response->assertStatus(500);
        $response->assertJsonPath(
            'error',
            '通信エラーが発生しました。',
        );
    }

    public function test_任意項目が存在しない場合_空文字で書籍情報を取得できる(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'totalItems' => 1,
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'Laravel入門',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->getJson('/books/isbn/9781234567890');

        $response->assertOk();
        $response->assertJson([
            'title' => 'Laravel入門',
            'author' => '',
            'published_date' => '',
            'description' => '',
            'image_url' => '',
        ]);
    }
}
