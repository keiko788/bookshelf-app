<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーはマイ読書レポート画面にアクセスできる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');
    }

    public function test_未認証ユーザーがマイ読書レポート画面にアクセスした場合ログイン画面にリダイレクトされる(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_自分が投稿したレビューの平均評価が正しく表示される(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Review::factory()->for($user)->create([
            'rating' => 5,
        ]);
        Review::factory()->for($user)->create([
            'rating' => 3,
        ]);
        Review::factory()->for($otherUser)->create([
            'rating' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            return $stats['summary']['average_rating'] === 4;
        });
    }

    public function test_自分が投稿した総レビュー数が正しく表示される(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Review::factory()->for($user)->count(2)->create();
        Review::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            return $stats['summary']['total_reviews'] === 2;
        });
    }

    public function test_読了冊数がユニーク書籍数として正しく表示される(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $firstBook = Book::factory()->create();
        $secondBook = Book::factory()->create();

        ReadingPlan::factory()->count(2)->create([
            'user_id' => $user->id,
            'book_id' => $firstBook->id,
            'completed_at' => now(),
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $secondBook->id,
            'completed_at' => now(),
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            return $stats['summary']['books_read'] === 2;
        });
    }

    public function test_評価分布で1から5の各評価ごとの件数を表示できる(): void
    {
        $user = User::factory()->create();

        Review::factory()->count(2)->for($user)->create([
            'rating' => 1,
        ]);

        Review::factory()->for($user)->create([
            'rating' => 2,
        ]);

        Review::factory()->count(3)->for($user)->create([
            'rating' => 3,
        ]);

        Review::factory()->for($user)->create([
            'rating' => 4,
        ]);

        Review::factory()->count(2)->for($user)->create([
            'rating' => 5,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            return $stats['rating_distribution']->all() ===
                [
                    2,
                    1,
                    3,
                    1,
                    2,
                ];
        });
    }

    public function test_高評価_to_p5が正しく表示される(): void
    {
        $user = User::factory()->create();
        $reviewerA = User::factory()->create();
        $reviewerB = User::factory()->create();

        $book1 = Book::factory()->create([
            'title' => '書籍1',
        ]);

        $book2 = Book::factory()->create([
            'title' => '書籍2',
        ]);

        $book3 = Book::factory()->create([
            'title' => '書籍3',
        ]);

        $book4 = Book::factory()->create([
            'title' => '書籍4',
        ]);

        $book5 = Book::factory()->create([
            'title' => '書籍5',
        ]);

        $book6 = Book::factory()->create([
            'title' => '書籍6',
        ]);

        Review::factory()->for($user)->create([
            'book_id' => $book1->id,
            'rating' => 5,
        ]);

        Review::factory()->for($reviewerA)->create([
            'book_id' => $book1->id,
            'rating' => 5,
        ]);

        Review::factory()->for($reviewerB)->create([
            'book_id' => $book1->id,
            'rating' => 5,
        ]);

        Review::factory()->for($user)->create([
            'book_id' => $book2->id,
            'rating' => 5,
        ]);

        Review::factory()->for($reviewerA)->create([
            'book_id' => $book2->id,
            'rating' => 5,
        ]);

        Review::factory()->for($user)->create([
            'book_id' => $book3->id,
            'rating' => 5,
        ]);

        Review::factory()->for($user)->create([
            'book_id' => $book4->id,
            'rating' => 4,
        ]);

        Review::factory()->for($reviewerA)->create([
            'book_id' => $book4->id,
            'rating' => 4,
        ]);

        Review::factory()->for($reviewerB)->create([
            'book_id' => $book4->id,
            'rating' => 4,
        ]);

        Review::factory()->for($user)->create([
            'book_id' => $book5->id,
            'rating' => 4,
        ]);

        Review::factory()->for($reviewerA)->create([
            'book_id' => $book5->id,
            'rating' => 4,
        ]);

        Review::factory()->for($user)->create([
            'book_id' => $book6->id,
            'rating' => 4,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            $topRatedBooks = $stats['top_rated_books'];

            return $topRatedBooks->count() === 5
                && $topRatedBooks->pluck('title')->all() ===
                [
                    '書籍1',
                    '書籍2',
                    '書籍3',
                    '書籍4',
                    '書籍5',
                ];
        });
    }

    public function test_ジャンル別評価傾向_to_p5が正しい順で表示される(): void
    {
        $user = User::factory()->create();

        $fiveStarBook = Book::factory()->create();

        $fourStarBookA = Book::factory()->create();

        $fourStarBookB = Book::factory()->create();

        $threeStarBook = Book::factory()->create();

        $twoStarBook = Book::factory()->create();

        $oneStarBook = Book::factory()->create();

        $firstRankGenre = Genre::factory()->create([
            'name' => '第1位ジャンル',
        ]);

        $secondRankGenre = Genre::factory()->create([
            'name' => '第2位ジャンル',
        ]);

        $thirdRankGenre = Genre::factory()->create([
            'name' => '第3位ジャンル',
        ]);

        $fourthRankGenre = Genre::factory()->create([
            'name' => '第4位ジャンル',
        ]);

        $fifthRankGenre = Genre::factory()->create([
            'name' => '第5位ジャンル',
        ]);

        $excludeGenre = Genre::factory()->create([
            'name' => 'ランク外ジャンル',
        ]);

        Review::factory()->for($user)->create([
            'book_id' => $fiveStarBook->id,
            'rating' => 5,
        ]);

        Review::factory()->for($user)->create([
            'book_id' => $fourStarBookA->id,
            'rating' => 4,
        ]);

        Review::factory()->for($user)->create([
            'book_id' => $fourStarBookB->id,
            'rating' => 4,
        ]);

        Review::factory()->for($user)->create([
            'book_id' => $threeStarBook->id,
            'rating' => 3,
        ]);

        Review::factory()->for($user)->create([
            'book_id' => $twoStarBook->id,
            'rating' => 2,
        ]);

        Review::factory()->for($user)->create([
            'book_id' => $oneStarBook->id,
            'rating' => 1,
        ]);

        $fiveStarBook->genres()->attach($firstRankGenre->id);

        $fourStarBookA->genres()->attach([
            $secondRankGenre->id,
            $thirdRankGenre->id,
        ]);

        $fourStarBookB->genres()->attach($secondRankGenre->id);

        $threeStarBook->genres()->attach([
            $fourthRankGenre->id,
            $fifthRankGenre->id,
        ]);

        $twoStarBook->genres()->attach($fifthRankGenre->id);

        $oneStarBook->genres()->attach($excludeGenre->id);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();

        $response->assertViewHas('stats', function ($stats) {
            $topRatedGenres = $stats['genre_ratings'];

            return $topRatedGenres->count() === 5
                && $topRatedGenres->pluck('name')->all() ===
                [
                    '第1位ジャンル',
                    '第2位ジャンル',
                    '第3位ジャンル',
                    '第4位ジャンル',
                    '第5位ジャンル',
                ]
                && $topRatedGenres->pluck('count')->all() ===
                [
                    1,
                    2,
                    1,
                    1,
                    2,
                ]
                && $topRatedGenres->pluck('average_rating')->all() ===
                [
                    5,
                    4,
                    4,
                    3,
                    2.5,
                ];
        });
    }

    public function test_レビュー0件でも正常に表示される(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertViewHas('stats', function ($stats) {
            return $stats['summary']['total_reviews'] === 0
            && $stats['rating_distribution']->all() === [0, 0, 0, 0, 0]
            && $stats['top_rated_books']->isEmpty()
            && $stats['genre_ratings']->isEmpty();
        });
    }
}
