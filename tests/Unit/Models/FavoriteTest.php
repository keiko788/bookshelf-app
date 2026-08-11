<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_お気に入りのリレーションが定義されている(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();
        $favorite = Favorite::factory()
            ->for($user)
            ->for($book)
            ->create();

        $this->assertTrue($favorite->user->is($user));
        $this->assertTrue($favorite->book->is($book));
    }
}
