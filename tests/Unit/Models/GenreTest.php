<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    public function test_ジャンルのリレーションが定義されている(): void
    {
        $book = Book::factory()->create();
        $genre = Genre::factory()->create();

        $genre->books()->attach($book);

        $this->assertTrue($genre->books->contains($book));
    }
}
