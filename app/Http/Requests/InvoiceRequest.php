<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;

class InvoiceRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    /**
     * Normalize incoming date strings so month/day are zero-padded (YYYY-MM-DD)
     * This runs before validation so tests and clients may send non-zero-padded dates like 2021-10-7
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('issue_date') && $this->input('issue_date') !== null && $this->input('issue_date') !== '') {
            $unpaddedDate = $this->input('issue_date');
            // attempt to parse using strtotime; if successful, reformat to Y-m-d
            $dateTime = strtotime($unpaddedDate);
            if ($dateTime !== false) {
                $this->merge(['issue_date' => date('Y-m-d', $dateTime)]);
            }
        }
    }

    public function rules(): array {
        // default values apply to POST requests
        $requiredIfPending = '|nullable|required_if:status,pending';
        $allowedStatus = 'required|string|in:draft,pending';
        $lineItemsRequirement = '|required_if:status,pending';
        // POST generates a new id but PUT/PATCH must include it
        $idRule = [];
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $idRule = ['id' => 'regex:/^[A-Z]{2}[0-9]{4}$/|required'];
            $allowedStatus = 'required|string|in:draft,pending,paid';
        }

        $rules = array_merge($idRule, [
            'issue_date' => 'date|date_format:Y-m-d|nullable' . $requiredIfPending,
            'description' => 'string|max:2000|nullable' . $requiredIfPending,
            'payment_terms' => 'integer|min:0|max:36525|nullable' . $requiredIfPending,
            'client_name' => 'string|max:100|nullable' . $requiredIfPending,
            'client_email' => 'email|max:100|nullable' . $requiredIfPending,
            'status' => $allowedStatus,
            'sender_address' => 'array|nullable' . $requiredIfPending,
            'sender_address.street' => 'string|max:100|nullable' . $requiredIfPending,
            'sender_address.city' => 'string|max:100|nullable' . $requiredIfPending,
            'sender_address.postal_code' => 'string|max:100|nullable' . $requiredIfPending,
            'sender_address.country' => 'string|max:100|nullable' . $requiredIfPending,
            'client_address' => 'array|nullable' . $requiredIfPending,
            'client_address.street' => 'string|max:100|nullable' . $requiredIfPending,
            'client_address.city' => 'string|max:100|nullable' . $requiredIfPending,
            'client_address.postal_code' => 'string|max:100|nullable' . $requiredIfPending,
            'client_address.country' => 'string|max:100|nullable' . $requiredIfPending,
            'line_items' => 'array' . $lineItemsRequirement,
            'line_items.*.name' => 'string|max:100|nullable' . $requiredIfPending,
            'line_items.*.quantity' => 'integer|min:0|nullable' . $requiredIfPending,
            'line_items.*.price_unit_cents' => 'integer|min:0|nullable' . $requiredIfPending,
        ]);

        return $rules;
    }

    /**
     * Handle a failed validation attempt so that 422 is returned instead of just throwing an exception.
     *
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator): void {
        // Instead of throwing an exception, you can return a custom response.
        // For example, returning a JSON response with validation errors:
        throw new HttpResponseException(response()->json([
            'message' => 'Request validation failed',
            'errors' => $validator->errors()
        ], 422));
    }
}
