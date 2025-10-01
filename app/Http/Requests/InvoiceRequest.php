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
    public function rules(): array {
        $reqIfPending = '|required_if:status,pending';
        $idRule = [];
         // POST generates a new id in the controller, PUT/PATCH already have ID
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $idRule = ['id' => 'regex:/^[A-Z]{2}[0-9]{4}$/|required_if:status,pending,paid'];
        }

        // A request must not PUT a paid invoice
        $allowedStatus = 'required|string|in:draft,pending,paid';
        if ($this->isMethod('POST')) {
            $allowedStatus = 'required|string|in:draft,pending';
        }

        $rules = array_merge($idRule, [
            'issue_date' => 'date|date_format:Y-m-d|nullable' . $reqIfPending,
            'description' => 'string|max:2000|nullable' . $reqIfPending,
            'payment_terms' => 'integer|min:0|max:36525|nullable' . $reqIfPending,
            'client_name' => 'string|max:100|nullable' . $reqIfPending,
            'client_email' => 'email|max:100|nullable' . $reqIfPending,
            'status' => $allowedStatus,
            'sender_address' => 'array|nullable' . $reqIfPending,
            'sender_address.street' => 'string|max:100|nullable' . $reqIfPending,
            'sender_address.city' => 'string|max:100|nullable' . $reqIfPending,
            'sender_address.postal_code' => 'string|max:100|nullable' . $reqIfPending,
            'sender_address.country' => 'string|max:100|nullable' . $reqIfPending,
            'client_address' => 'array|nullable' . $reqIfPending,
            'client_address.street' => 'string|max:100|nullable' . $reqIfPending,
            'client_address.city' => 'string|max:100|nullable' . $reqIfPending,
            'client_address.postal_code' => 'string|max:100|nullable' . $reqIfPending,
            'client_address.country' => 'string|max:100|nullable' . $reqIfPending,
            'line_items' => 'array|nullable' . $reqIfPending,
            'line_items.*.name' => 'string|max:100|nullable' . $reqIfPending,
            'line_items.*.quantity' => 'integer|min:0|nullable' . $reqIfPending,
            'line_items.*.price_unit_cents' => 'integer|min:0|nullable' . $reqIfPending,
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
