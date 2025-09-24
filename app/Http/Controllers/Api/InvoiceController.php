<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoicesResourceCollection;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     * Homepage should hit this endpoint
     */
    public function index()
    {
        $invoices = Invoice::all();
        return new InvoicesResourceCollection($invoices)
            ->response()
            ->setStatusCode(200)
            ->setEncodingOptions(JSON_PRETTY_PRINT);
    }

    /**
     * Store a newly created resource in storage.
     * "Add New Invoice" plus any save should hit this endpoint.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     * Selecting an invoince should hit this endpoint.
     */
    public function show(Invoice $invoice)
    {
        return new InvoiceResource($invoice)
            ->response()
            ->setStatusCode(200)
            ->setEncodingOptions(JSON_PRETTY_PRINT);
    }

    /**
     * Update the specified resource in storage.
     * "Edit" button plus any save should hit this endpoint.
     */
    public function update(Request $request, Invoice $invoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * "Delete" Button hits this endpoint.
     * Deletion clusterfuck cascade is defined in deleting event of Invoice
     */
    public function destroy(Invoice $invoice)
    {
        //
    }
}
