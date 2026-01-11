<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetPreciousMetalsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('metal')) {
            $this->merge(['metal' => strtoupper($this->input('metal'))]);
        }
        if ($this->has('unit')) {
            $this->merge(['unit' => strtoupper($this->input('unit'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Using 'in' ensures Scramble renders a Select dropdown
            'metal' => ['nullable', 'string', 'in:GOLD,SILVER,PLATINUM,PALLADIUM,RHODIUM'],
            'unit' => ['nullable', 'string', 'in:GRAM,OZ'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'time' => ['nullable', 'string'],
        ];
    }

    /**
     * Get the query parameters for API documentation (Scramble).
     */
    public function queryParameters(): array
    {
        return [
            'metal' => [
                'description' => 'The metal name (e.g., GOLD). Defaults to GOLD.',
                'example' => 'GOLD',
            ],
            'unit' => [
                'description' => 'Unit of measurement: GRAM (default) or OZ.',
                'example' => 'GRAM',
            ],
            'date' => [
                'description' => 'Filter by date (Y-m-d).',
                'example' => '2026-01-09',
            ],
            'time' => [
                'description' => 'Filter by time (H:i or H). Matches closest minute or latest in hour.',
                'example' => '17:00',
            ],
        ];
    }
}
