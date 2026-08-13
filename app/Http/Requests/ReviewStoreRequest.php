<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewStoreRequest extends FormRequest
{
    /**
     * リクエストの実行を許可するか判定する。
     *
     * @return bool リクエストを許可する場合はtrue
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールを定義する。
     *
     * @return array<string, string> バリデーションルール
     */
    public function rules(): array
    {
        return [
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string|max:255',
        ];
    }

    /**
     * バリデーションエラーメッセージを定義する。
     *
     * @return array<string, string> バリデーションエラーメッセージ
     */
    public function messages(): array
    {
        return [
            'rating.required' => '評価を選択してください。',
            'rating.integer' => '評価が不正です。',
            'rating.between' => '評価は1から5の範囲で選択してください。',
            'comment.required' => 'レビューを入力してください。',
            'comment.string' => 'レビューは文字列で入力してください。',
            'comment.max' => 'レビューは255文字以内で入力してください。',
        ];
    }
}
