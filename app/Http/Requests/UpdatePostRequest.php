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
        // Otorisasi ditangani oleh PostPolicy yang dipanggil dari controller.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string'],
            'is_draft' => ['sometimes', 'boolean'],
            'published_at' => [
                'nullable',
                'date',
                \Illuminate\Validation\Rule::requiredIf(function () {
                    // Require published_at if is_draft is being set to false
                    $isDraft = $this->boolean('is_draft');

                    // If is_draft is present and false, require published_at
                    return $this->has('is_draft') && $isDraft === false;
                }),
            ],
        ];
    }
}
