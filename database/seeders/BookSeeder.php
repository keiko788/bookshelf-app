<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        $books = [
            [
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'isbn' => '9784101010014',
                'published_date' => '1905-01-01',
                'description' => '猫の視点から人間社会を風刺的に描いた夏目漱石の代表作です。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=1',
                'genre_names' => ['小説'],
            ],
            [
                'title' => '人を動かす',
                'author' => 'D・カーネギー',
                'isbn' => '9784422100524',
                'published_date' => '1936-10-01',
                'description' => '人間関係を築き、相手の心を動かすための原則をまとめた一冊です。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=2',
                'genre_names' => ['ビジネス', '自己啓発'],
            ],
            [
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell',
                'isbn' => '9784873115658',
                'published_date' => '2012-06-23',
                'description' => '「動けばいいコード」から一歩進んで、読みやすいコードを書くための基準を学べる一冊。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=3',
                'genre_names' => ['技術書'],
            ],
            [
                'title' => '7つの習慣',
                'author' => 'スティーブン・R・コヴィー',
                'isbn' => '9784863940246',
                'published_date' => '2013-08-30',
                'description' => '継続的な成功を収めるための原理原則をまとめた人生哲学です。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=4',
                'genre_names' => ['ビジネス', '自己啓発'],
            ],
            [
                'title' => '坊っちゃん',
                'author' => '夏目漱石',
                'isbn' => '9784101010021',
                'published_date' => '1906-04-01',
                'description' => '夏目漱石による日本の中編小説。漱石初期の青春小説である。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=5',
                'genre_names' => ['小説'],
            ],
            [
                'title' => 'サピエンス全史',
                'author' => 'ユヴァル・ノア・ハラリ',
                'isbn' => '9784309226712',
                'published_date' => '2016-09-08',
                'description' => 'なぜ我々はこのような世界に生きているのか?ホモ・サピエンスの歴史を俯瞰することで現代世界を鋭く抉る世界的ベストセラー!',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=6',
                'genre_names' => ['歴史', '科学'],
            ],
            [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '9784048930598',
                'published_date' => '2017-12-18',
                'description' => 'Clean Code――すなわち、綺麗で読みやすいコードを書くための様々なテクニックや考え方を紹介している。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=7',
                'genre_names' => ['技術書'],
            ],
            [
                'title' => '嫌われる勇気',
                'author' => '岸見一郎・古賀史健',
                'isbn' => '9784478025819',
                'published_date' => '2013-12-13',
                'description' => '哲学者・岸見一郎とライター・古賀史健の共著による大ベストセラー自己啓発書です。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=8',
                'genre_names' => ['自己啓発'],
            ],
            [
                'title' => '火花',
                'author' => '又吉直樹',
                'isbn' => '9784163902302',
                'published_date' => '2015-03-11',
                'description' => 'お笑いコンビ「ピース」の又吉直樹による処女小説で、第153回芥川賞を受賞した大ベストセラー作品です。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=9',
                'genre_names' => ['小説'],
            ],
            [
                'title' => 'FACTFULNESS',
                'author' => 'ハンス・ロスリング',
                'isbn' => '9784822289607',
                'published_date' => '2019-01-11',
                'description' => '世界についての間違った思い込み（ネガティブすぎる見方など）を指摘し、データや事実に基づいて世界を正しく見る習慣（ファクトフルネス）を教えてくれる世界的ベストセラー本',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=10',
                'genre_names' => ['ビジネス', '科学'],
            ],
            [
                'title' => 'コンテナ物語',
                'author' => 'マルク・レビンソン',
                'isbn' => '9784822251468',
                'published_date' => '2007-01-18',
                'description' => '物流を劇的に変え、世界経済のグローバル化を牽引した「輸送用コンテナ」の誕生と普及の歴史を描いた傑作ノンフィクションです。',
                'image_url' => 'https://placehold.co/200x300/e2e8f0/475569?text=11',
                'genre_names' => ['ビジネス', '歴史'],
            ],
        ];

        $genres = Genre::pluck('id', 'name');

        foreach ($books as $bookData) {

            $genreNames = $bookData['genre_names'];

            unset($bookData['genre_names']);

            $book = Book::firstOrCreate(
                ['isbn' => $bookData['isbn']],
                array_merge(
                    $bookData,
                    ['user_id' => $user->id]
                )
            );

            $genreIds = [];

            foreach ($genreNames as $genreName) {
                $genreIds[] = $genres[$genreName];
            }

            $book->genres()->sync($genreIds);
        }
    }
}
