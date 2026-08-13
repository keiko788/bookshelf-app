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
        $ratingComments = [
            1 => '期待していた内容とは少し違いました。',
            2 => '参考になる部分もありましたが、少し物足りなく感じました。',
            3 => '読みやすく、全体的に楽しめました。',
            4 => 'とても参考になり、満足できる内容でした。',
            5 => '非常に満足できる内容で、ぜひおすすめしたい一冊です。',
        ];

        $users = User::all();
        $books = Book::all();

        $books->each(function ($book) use ($ratingComments, $users) {
            $reviewCount = random_int(2, 4);

            $reviewUsers = $users->random($reviewCount);

            $reviewUsers->each(function ($reviewUser) use ($ratingComments, $book) {
                $rating = random_int(1, 5);

                Review::create([
                    'user_id' => $reviewUser->id,
                    'book_id' => $book->id,
                    'rating' => $rating,
                    'comment' => $ratingComments[$rating],
                ]);
            });
        });
    }
}
