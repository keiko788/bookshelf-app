<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => [
                'required',
                'regex:/^\d{13}$/',
                Rule::unique('books', 'isbn')->ignore($this->book),
            ],
            'published_date' => 'required|date',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:255',
            'genres' => 'required|array|min:1',
            'genres.*' => 'integer|exists:genres,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください',
            'title.string' => 'タイトルは文字列で入力してください',
            'title.max' => 'タイトルは255文字以内で入力してください',
            'author.required' => '著者名を入力してください',
            'author.string' => '著者名は文字列で入力してください',
            'author.max' => '著者名は255文字以内で入力してください',
            'isbn.required' => 'ISBNを入力してください',
            'isbn.regex' => 'ISBNは13桁で入力してください',
            'isbn.unique' => 'このISBNは既に登録されています',
            'published_date.required' => '出版日を入力してください',
            'published_date.date' => '出版日は有効な日付で入力してください',
            'description.string' => '説明は文字列で入力してください',
            'image_url.url' => '画像URLは有効なURLを入力してください',
            'image_url.max' => '画像URLは255文字以内で入力してください',
            'genres.required' => 'ジャンルを選択してください',
            'genres.array' => 'ジャンルの指定が正しくありません',
            'genres.min' => 'ジャンルを1つ以上選択してください',
            'genres.*.integer' => 'ジャンルの指定が正しくありません',
            'genres.*.exists' => '選択されたジャンルが存在しません',
        ];
    }
}
