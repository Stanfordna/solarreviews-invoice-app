<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * ResourceCollection class to assist in returning a list 
 * of invoices in the InvoiceController index method
 */
class InvoicesResourceCollection extends ResourceCollection
{
    public $collects = InvoiceResource::class;

    /**
     * Transform the resource collection into an array.
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }

    /**
     * Add metadata to the list of invoices,
     * at the top level of the response next to "data".
     * @return array<int|string, mixed>
     */
    public function with($request)
    {
        return [
            'meta' => [
                'count' => $this->collection->count(),
                'api_version' => '1.0' 
            ],
        ];
    }
}
