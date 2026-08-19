<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReadingPlanStoreRequest extends FormRequest
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
            'book_id' => 'required|integer|exists:books,id',
            'target_date' => 'required|date|after_or_equal:today',
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
            'book_id.required' => '書籍を選択してください。',
            'book_id.exists' => '選択された書籍は存在しません。',
            'target_date.required' => '期日を入力してください。',
            'target_date.date' => '期日は有効な日付で入力してください。',
            'target_date.after_or_equal' => '期日は今日以降の日付を指定してください。',
        ];
    }
}
