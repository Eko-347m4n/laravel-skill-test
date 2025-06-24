<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Memastikan pengguna yang terotentikasi adalah pemilik post yang akan di-update.
        // $this->route('post') akan mengambil model Post dari route model binding.
        return $this->user()->id === $this->route('post')->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // Menggunakan 'sometimes' memastikan bahwa aturan hanya diterapkan
        // jika field tersebut ada dalam request. Ini ideal untuk request PATCH/PUT.
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['sometimes', 'required', 'string'],
            'is_draft' => ['sometimes', 'required', 'boolean'],
            // 'published_at' tetap wajib jika post tidak dalam status draft.
            'published_at' => ['nullable', 'date', 'required_if:is_draft,false'],
        ];
    }
}
