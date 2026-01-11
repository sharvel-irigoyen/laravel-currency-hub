<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowPreciousMetalRequest extends FormRequest
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
        if ($this->has('purity')) {
            $this->merge(['purity' => strtoupper($this->input('purity'))]);
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
        // Define allowable purities per metal
        // This validation is a bit complex for a simple array rule if we want per-metal checks.
        // For simplicity and "Robustness", we can allow the union of all purities in the rule,
        // and optionally refine in specific custom rules if strictness is required.
        // Given the prompt "best practices" usually implies strictness...
        // I will use a custom closure or simplistic 'in' list for now to cover all.
        // Or better: strict lists.

        return [
            'unit' => ['nullable', 'string', 'in:GRAM,OZ'],
            // 'date' validation rule usually maps to format:date in OpenAPI => Calendar UI
            'date' => ['sometimes', 'date'],
            'time' => ['sometimes', 'string'],
            'purity' => [
                'nullable',
                'string',
                // Superset validation helps Scramble generate a Select Dropdown for UI.
                'in:24K,22K,18K,14K,10K,999,958,STERLING,925,BRITANNIA,900,COIN,950,850,500',
                function ($attribute, $value, $fail) {
                    $metalParam = $this->route('metal');
                    // Handle Enum binding (Laravel 9+ automatically binds Enums in routes)
                    $metal = $metalParam instanceof \App\Enums\PreciousMetalType
                        ? $metalParam->value
                        : strtoupper($metalParam);

                    $validPurities = match($metal) {
                        'GOLD' => ['24K', '22K', '18K', '14K', '10K'],
                        'SILVER' => ['999', '958', 'STERLING', '925', '900'],
                        'PLATINUM' => ['999', '950', '900', '850'],
                        'PALLADIUM' => ['999', '950', '500'],
                        'RHODIUM' => ['999'],
                        default => [] // Validation will fall through? Or metal 404s earlier.
                    };

                    if (!in_array($value, $validPurities)) {
                        $fail("The selected purity ($value) is invalid for $metal. Allowed: " . implode(', ', $validPurities));
                    }
                }
            ],
        ];
    }

    /**
     * Get the query parameters for API documentation (Scramble).
     */
    public function queryParameters(): array
    {
        return [
            'purity' => [
                'description' => 'Commercial Purity/Fineness. Valid values depend on the metal.',
                'example' => '18K',
            ],
            'unit' => [
                'description' => 'Unit of measurement: GRAM (default) or OZ.',
                'example' => 'GRAM',
            ],
            'date' => [
                'description' => 'Filter by date (Y-m-d).',
                'example' => '2026-01-09',
                'schema' => [
                    'type' => 'string',
                    'format' => 'date', // Calendar UI
                ],
            ],
            'time' => [
                'description' => 'Filter by time (H:i or H). Matches closest minute or latest in hour.',
                'example' => '17:00',
                'schema' => [
                    'type' => 'string',
                    'format' => 'time', // Time Picker
                ],
            ],
        ];
    }
}
