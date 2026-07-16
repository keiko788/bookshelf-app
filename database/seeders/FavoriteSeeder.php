<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $favorites = [

            'yamada@example.com' => [
                '9784101010014',
                '9784873115658',
                '9784048930598',
                '9784163902302',
            ],
            'suzuki@example.com' => [
                '9784422100524',
                '9784478025819',
                '9784822289607',
            ],
            'tanaka@example.com' => [
                '9784873115658',
                '9784863940246',
                '9784048930598',
                '9784163902302',
                '9784822251468',
            ],
            'sato@example.com' => [
                '9784863940246',
                '9784101010021',
                '9784309226712',
                '9784163902302',
                '9784822251468',
            ],
            'takahashi@example.com' => [
                '9784101010014',
                '9784422100524',
                '9784101010021',
                '9784048930598',
                '9784822251468',
            ],

        ];

        $userIds = User::pluck('id', 'email');
        $bookIds = Book::pluck('id', 'isbn');

        foreach ($favorites as $email => $isbns) {
            $favoriteBookIds = [];

            foreach ($isbns as $isbn) {
                $favoriteBookIds[] = $bookIds[$isbn];
            }

            $user = User::find($userIds[$email]);

            $user->favoriteBooks()->syncWithoutDetaching($favoriteBookIds);
        }
    }
}
