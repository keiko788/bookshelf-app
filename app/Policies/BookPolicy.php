<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /**
     * 書籍登録者本人のみ更新を許可する。
     *
     * @param  User  $user  更新を行うユーザー
     * @param  Book  $book  更新対象の書籍
     * @return bool 更新を許可する場合はtrue
     */
    public function update(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * 書籍登録者本人のみ削除を許可する。
     *
     * @param  User  $user  削除を行うユーザー
     * @param  Book  $book  削除対象の書籍
     * @return bool 削除を許可する場合はtrue
     */
    public function delete(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }
}
