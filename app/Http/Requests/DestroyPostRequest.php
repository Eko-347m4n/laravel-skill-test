<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Memastikan pengguna yang terotentikasi adalah pemilik post yang akan dihapus.
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
        // Tidak ada input yang perlu divalidasi untuk permintaan DELETE.
        return [];
    }
}
