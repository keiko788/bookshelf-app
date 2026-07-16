<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reviews = [
            [
                'user_email' => 'yamada@example.com',
                'isbn' => '9784101010014',
                'rating' => 5,
                'comment' => '猫の視点で描かれる物語がとても面白く、何度でも読み返したくなる作品でした。',
            ],
            [
                'user_email' => 'tanaka@example.com',
                'isbn' => '9784101010014',
                'rating' => 4,
                'comment' => '少し古い表現もありますが、ユーモアがあり楽しく読めました。',
            ],
            [
                'user_email' => 'takahashi@example.com',
                'isbn' => '9784101010014',
                'rating' => 3,
                'comment' => '文学作品として有名ですが、自分には少し難しく感じました。',
            ],
            [
                'user_email' => 'suzuki@example.com',
                'isbn' => '9784422100524',
                'rating' => 5,
                'comment' => '人間関係の基本が分かりやすく書かれていて仕事にも役立ちます。',
            ],
            [
                'user_email' => 'tanaka@example.com',
                'isbn' => '9784422100524',
                'rating' => 5,
                'comment' => '時代が変わっても通用する内容で非常に勉強になりました。',
            ],
            [
                'user_email' => 'sato@example.com',
                'isbn' => '9784422100524',
                'rating' => 5,
                'comment' => 'ビジネスだけでなく日常生活でも活かせる考え方が多かったです。',
            ],
            [
                'user_email' => 'takahashi@example.com',
                'isbn' => '9784422100524',
                'rating' => 5,
                'comment' => '初めて自己啓発本を読みましたが、とても読みやすかったです。',
            ],
            [
                'user_email' => 'yamada@example.com',
                'isbn' => '9784873115658',
                'rating' => 5,
                'comment' => 'プログラマーなら一度は読むべき一冊だと思います。',
            ],
            [
                'user_email' => 'suzuki@example.com',
                'isbn' => '9784873115658',
                'rating' => 5,
                'comment' => 'コードを書く意識が大きく変わりました。',
            ],
            [
                'user_email' => 'tanaka@example.com',
                'isbn' => '9784873115658',
                'rating' => 5,
                'comment' => '実例が多く、とても理解しやすかったです。',
            ],
            [
                'user_email' => 'sato@example.com',
                'isbn' => '9784873115658',
                'rating' => 5,
                'comment' => '新人だけでなく経験者にもおすすめです。',
            ],
            [
                'user_email' => 'yamada@example.com',
                'isbn' => '9784863940246',
                'rating' => 5,
                'comment' => '人生や仕事への考え方が変わる一冊でした。',
            ],
            [
                'user_email' => 'sato@example.com',
                'isbn' => '9784863940246',
                'rating' => 5,
                'comment' => 'ボリュームはありますが読む価値があります。',
            ],
            [
                'user_email' => 'takahashi@example.com',
                'isbn' => '9784863940246',
                'rating' => 4,
                'comment' => '内容は素晴らしいですが少し難しく感じました。',
            ],
            [
                'user_email' => 'yamada@example.com',
                'isbn' => '9784101010021',
                'rating' => 5,
                'comment' => '今読んでも十分楽しめる名作です。',
            ],
            [
                'user_email' => 'suzuki@example.com',
                'isbn' => '9784101010021',
                'rating' => 4,
                'comment' => 'テンポが良く読みやすい作品でした。',
            ],
            [
                'user_email' => 'tanaka@example.com',
                'isbn' => '9784101010021',
                'rating' => 4,
                'comment' => '登場人物の個性が印象的でした。',
            ],
            [
                'user_email' => 'suzuki@example.com',
                'isbn' => '9784309226712',
                'rating' => 5,
                'comment' => '人類の歴史を広い視点で学べました。',
            ],
            [
                'user_email' => 'sato@example.com',
                'isbn' => '9784309226712',
                'rating' => 5,
                'comment' => 'とても興味深く、一気に読み切りました。',
            ],
            [
                'user_email' => 'takahashi@example.com',
                'isbn' => '9784309226712',
                'rating' => 4,
                'comment' => '内容は濃いですが少し難易度が高めです。',
            ],
            [
                'user_email' => 'yamada@example.com',
                'isbn' => '9784048930598',
                'rating' => 5,
                'comment' => '保守しやすいコードを書く重要性が理解できました。',
            ],
            [
                'user_email' => 'tanaka@example.com',
                'isbn' => '9784048930598',
                'rating' => 4,
                'comment' => '実務でもすぐに活かせる内容でした。',
            ],
            [
                'user_email' => 'sato@example.com',
                'isbn' => '9784048930598',
                'rating' => 4,
                'comment' => '難しい部分もありましたが勉強になりました。',
            ],
            [
                'user_email' => 'suzuki@example.com',
                'isbn' => '9784478025819',
                'rating' => 5,
                'comment' => '考え方が大きく変わるきっかけになりました。',
            ],
            [
                'user_email' => 'tanaka@example.com',
                'isbn' => '9784478025819',
                'rating' => 4,
                'comment' => '対話形式なので読みやすかったです。',
            ],
            [
                'user_email' => 'sato@example.com',
                'isbn' => '9784478025819',
                'rating' => 3,
                'comment' => '内容は興味深いですが、考え方が自分にはあまり合いませんでした。',
            ],
            [
                'user_email' => 'yamada@example.com',
                'isbn' => '9784163902302',
                'rating' => 4,
                'comment' => '芸人同士の人間関係がリアルに描かれていました。',
            ],
            [
                'user_email' => 'suzuki@example.com',
                'isbn' => '9784163902302',
                'rating' => 3,
                'comment' => '独特な世界観でしたが、期待していたほどではありませんでした。',
            ],
            [
                'user_email' => 'sato@example.com',
                'isbn' => '9784822289607',
                'rating' => 5,
                'comment' => 'データを見る視点が大きく変わりました。',
            ],
            [
                'user_email' => 'takahashi@example.com',
                'isbn' => '9784822289607',
                'rating' => 4,
                'comment' => '数字が多いですが面白く読めました。',
            ],
            [
                'user_email' => 'suzuki@example.com',
                'isbn' => '9784822251468',
                'rating' => 5,
                'comment' => '物流に興味がなくても楽しめる内容でした。',
            ],
            [
                'user_email' => 'tanaka@example.com',
                'isbn' => '9784822251468',
                'rating' => 3,
                'comment' => '内容は勉強になりましたが、専門的な話が多く読むのに時間がかかりました。',
            ],
        ];

        $users = User::pluck('id', 'email');
        $books = Book::pluck('id', 'isbn');

        foreach ($reviews as $review) {
            Review::create([
                'user_id' => $users[$review['user_email']],
                'book_id' => $books[$review['isbn']],
                'rating' => $review['rating'],
                'comment' => $review['comment'],
            ]);
        }
    }
}
