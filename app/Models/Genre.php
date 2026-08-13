<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * ジャンルに紐づく書籍とのリレーションを取得する。
     *
     * @return BelongsToMany 書籍とのリレーション
     */
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class);
    }
}
