<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class IndexBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string'],
            'genre_id' => ['nullable', 'integer', 'exists:genres,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'keyword.string' => 'キーワードは文字列で入力してください。',
            'genre_id.integer' => 'ジャンルIDは整数で入力してください。',
            'genre_id.exists' => '選択されたジャンルは存在しません。',
            'page.integer' => 'ページ番号は整数で入力してください。',
            'page.min' => 'ページ番号は1以上で入力してください。',
            'per_page.integer' => 'ページあたり件数は整数で入力してください。',
            'per_page.min' => 'ページあたり件数は1以上で入力してください。',
        ];
    }
}
