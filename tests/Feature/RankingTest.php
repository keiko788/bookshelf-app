<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ランキング画面に書籍名_著者名_平均評価_レビュー件数が表示される(): void
    {
        $book = Book::factory()->create([
            'title' => 'ランキングテスト',
            'author' => 'テスト著者',
        ]);

        Review::factory()->for($book)->create([
            'rating' => 5,
        ]);

        Review::factory()->for($book)->create([
            'rating' => 4,
        ]);

        Review::factory()->for($book)->create([
            'rating' => 3,
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSee('ランキングテスト');
        $response->assertSee(
            'テスト著者',
            $response->assertSee('4.00')
        );
        $response->assertSee('3件');
    }

    public function test_ランキングは平均評価が高い順に表示される(): void
    {
        $book1 = Book::factory()->create([
            'title' => '平均評価4.5',
        ]);

        $book2 = Book::factory()->create([
            'title' => '平均評価3.5',
        ]);

        $book3 = Book::factory()->create([
            'title' => '平均評価2.5',
        ]);

        Review::factory()->for($book1)->create(['rating' => 5]);
        Review::factory()->for($book1)->create(['rating' => 4]);

        Review::factory()->for($book2)->create(['rating' => 4]);
        Review::factory()->for($book2)->create(['rating' => 3]);

        Review::factory()->for($book3)->create(['rating' => 3]);
        Review::factory()->for($book3)->create(['rating' => 2]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $response->assertSeeInOrder([
            '平均評価4.5',
            '平均評価3.5',
            '平均評価2.5',
        ]);
    }

    public function test_平均評価が同じ書籍はデータ登録順に表示される(): void
    {
        $book1 = Book::factory()->create([
            'title' => '最初に登録した書籍',
        ]);

        $book2 = Book::factory()->create([
            'title' => '2番目に登録した書籍',
        ]);

        $book3 = Book::factory()->create([
            'title' => '3番目に登録した書籍',
        ]);

        Review::factory()->for($book1)->create(['rating' => 4]);
        Review::factory()->for($book2)->create(['rating' => 4]);
        Review::factory()->for($book3)->create(['rating' => 4]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $response->assertSeeInOrder([
            '最初に登録した書籍',
            '2番目に登録した書籍',
            '3番目に登録した書籍',
        ]);
    }

    public function test_ランキングは上位10件のみ表示される(): void
    {
        $books = Book::factory()->count(11)->create();

        foreach ($books as $book) {
            Review::factory()->for($book)->create([
                'rating' => 4,
            ]);
        }

        $response = $this->get(route('ranking.index'));

        $response->assertOk();

        $response->assertViewHas(
            'rankedBooks',
            fn ($rankedBooks) => $rankedBooks->count() === 10
        );
    }

    public function test_レビューのない書籍はランキング対象外になる(): void
    {
        $book1 = Book::factory()->create([
            'title' => 'ランキング対象書籍A',
        ]);
        $book2 = Book::factory()->create([
            'title' => 'ランキング対象書籍B',
        ]);
        $book3 = Book::factory()->create([
            'title' => 'ランキング対象外書籍',
        ]);

        Review::factory()->for($book1)->create();
        Review::factory()->for($book2)->create();

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSee('ランキング対象書籍A');
        $response->assertSee('ランキング対象書籍B');
        $response->assertDontSee('ランキング対象外書籍');

        $response->assertViewHas(
            'rankedBooks',
            fn ($rankedBooks) => $rankedBooks->count() === 2
        );
    }

    public function test_書籍情報ないの平均評価が小数点第2位まで表示される(): void
    {
        $book1 = Book::factory()->create([
            'title' => 'ランキング対象書籍A',
        ]);
        $book2 = Book::factory()->create([
            'title' => 'ランキング対象書籍B',
        ]);
        Book::factory()->create([
            'title' => 'ランキング対象外書籍',
        ]);

        Review::factory()->for($book1)->create();
        Review::factory()->for($book2)->create();

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSee('ランキング対象書籍A');
        $response->assertSee('ランキング対象書籍B');
        $response->assertDontSee('ランキング対象外書籍');

        $response->assertViewHas(
            'rankedBooks',
            fn ($rankedBooks) => $rankedBooks->count() === 2
        );
    }
}
