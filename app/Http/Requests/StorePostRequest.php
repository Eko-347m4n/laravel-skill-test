<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Memastikan hanya user yang terotentikasi yang bisa membuat post.
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            // Menggunakan 'body' sesuai dengan kolom di database Anda.
            'body' => ['required', 'string'],
            'is_draft' => ['required', 'boolean'],
            'published_at' => ['nullable', 'date', 'required_if:is_draft,false'],
        ];
    }
}
