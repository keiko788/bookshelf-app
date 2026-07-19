<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReviewStoreRequest extends FormRequest
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
            'rating' => 'required|integer|between:1,5',
            'comment' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => '評価を選択してください',
            'rating.integer' => '評価が不正です',
            'rating.between' => '評価は1から5の範囲で選択してください',
            'comment.required' => 'レビューを入力してください',
            'comment.string' => 'レビューは文字列で入力してください',
            'comment.max' => 'レビューは255文字以内で入力してください',
        ];
    }
}
