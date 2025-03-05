<?php

declare(strict_types=1);

namespace App\NewYorkTimes\UserInterface\Api\V1\Request;

use Illuminate\Foundation\Http\FormRequest;

class BestSellersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nyt_api_key' => 'nullable|string',
            'author' => 'nullable|string',
            'isbn' => 'nullable|array',
            'isbn.*' => 'string',
            'title' => 'nullable|string',
            'offset' => 'nullable|integer|min:0',
        ];
    }
}