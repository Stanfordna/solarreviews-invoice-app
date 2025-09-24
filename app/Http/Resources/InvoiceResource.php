<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'issue_date' => $this->issue_date,
            'due_date' => $this->due_date,
            'description' => $this->description,
            'payment_terms' => $this->payment_terms,
            'client_name' => $this->client?->full_name,
            'client_email' => $this->client?->email,
            'status' => $this->status,
            'sender_address' => [
                'street' => $this->senderAddress?->street,
                'city' => $this->senderAddress?->city,
                'postal_code' => $this->senderAddress?->postal_code,
                'country' => $this->senderAddress?->country
            ],
            'client_address' => [
                'street' => $this->clientAddress?->street,
                'city' => $this->clientAddress?->city,
                'postal_code' => $this->clientAddress?->postal_code,
                'country' => $this->clientAddress?->country
            ],
            'line_items' => LineItemResource::collection($this->lineItems),
            'total_cents' => $this->total_cents,
        ];
    }

    /**
     * Add metadata to the single invoice response,
     * at the top level of the response next to "data".
     * @return array<int|string, mixed>
     */
    public function with($request)
    {
        return [
            'meta' => [
                'api_version' => '1.0' 
            ],
        ];
    }
}
