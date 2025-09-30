<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvoicesResourceCollection;
use App\Http\Resources\InvoiceResource;
use App\Models\Client;
use App\Models\Address;
use App\Models\Invoice;
use App\Http\Requests\InvoiceRequest;
use App\Models\LineItem;

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
     * "Add New Invoice" plus any save - draft or pending - should hit this endpoint.
     */
    public function store(InvoiceRequest $request)
    {
        $data = $request->all();

        // drafts will always produce new client/address records, even if they are temporary duplicates
        if ($data['status'] == 'pending') { 
            // for pending, attempt to find preexisting client/addresses to reference in new invoice.
            $client = Client::where('full_name', $data['client_name'])
                ->where('email', $data['client_email'])
                ->first();
                
            $senderAddress = Address::where('street', $data['client_address']['street'])
                ->where('city', $data['client_address']['city'])
                ->where('postal_code', $data['client_address']['postal_code'])
                ->where('country', $data['client_address']['country'])
                ->first();

            $clientAddress = Address::where('street', $data['client_address']['street'])
                ->where('city', $data['client_address']['city'])
                ->where('postal_code', $data['client_address']['postal_code'])
                ->where('country', $data['client_address']['country'])
                ->first();
        }

        // isSet returns FALSE if $variable has not been assigned or if it is NULL.
        // create records for client/address if invoice is a draft or pending invoice references new clinet/addresses
        if (!isset($client)) {
            $clientData = [];
            $clientData['full_name'] = $data['client_name'] ?? "";
            $clientData['email'] = $data['client_email'] ?? "";
            $client = Client::create($clientData);
        }
        if (!isset($senderAddress)) {
            $senderAddressData = [];
            $senderAddressData['street'] = $data['sender_address']['street'] ?? "";
            $senderAddressData['city'] = $data['sender_address']['city'] ?? "";
            $senderAddressData['postal_code'] = $data['sender_address']['postal_code'] ?? "";
            $senderAddressData['country'] = $data['sender_address']['country'] ?? "";
            $senderAddress = Address::create($senderAddressData);
        }
        if (!isset($clientAddress)) {
            $clientAddressData = [];
            $clientAddressData['street'] = $data['client_address']['street'] ?? "";
            $clientAddressData['city'] = $data['client_address']['city'] ?? "";
            $clientAddressData['postal_code'] = $data['client_address']['postal_code'] ?? "";
            $clientAddressData['country'] = $data['client_address']['country'] ?? "";
            $clientAddress = Address::create($clientAddressData);
        }

        $invoiceTotal = 0;
        if (!isset($data['line_items'])) $data['line_items'] = [];
        foreach ($data['line_items'] as $lineItem) {
            $lineItem['price_total_cents'] = $lineItem['price_unit_cents'] * $lineItem['quantity'];
            $invoiceTotal += $lineItem['price_total_cents'];
        }

        // calculate due date from payment terms
        if (!isset($data['issue_date']) || !isset($data['payment_terms'])) {
            $dueDate = null;
        } else {
            $dueDate = date('Y-m-d', strtotime("{$data['issue_date']} +{$data['payment_terms']} days"));
        }


        $newInvoiceData = [];
        $newInvoiceData['issue_date'] = $data['issue_date'] ?? "";
        $newInvoiceData['due_date'] = $dueDate ?? "";
        $newInvoiceData['description'] = $data['description'] ?? "";
        $newInvoiceData['payment_terms'] = $data['payment_terms'] ?? 0;
        $newInvoiceData['client_id'] = $client->id;
        $newInvoiceData['status'] = $data['status'];
        $newInvoiceData['sender_address_id'] = $senderAddress->id;
        $newInvoiceData['client_address_id'] = $clientAddress->id;
        $newInvoiceData['total_cents'] = $invoiceTotal;
        $newInvoice = Invoice::create($newInvoiceData);

        // create line items using new invoice id for foreign key
        foreach ($data['line_items'] as $lineItem) {
            // for a post request we are making all new line items, not updating or removing existing ones.
            LineItem::create([
                'invoice_id' => $newInvoice->id,
                'name' => $lineItem['name'],
                'quantity' => $lineItem['quantity'],
                'price_unit_cents' => $lineItem['price_unit_cents'],
                'price_total_cents' => $lineItem['price_unit_cents'] * $lineItem['quantity']
            ]);
        }

        // Return the invoice ID in the response
        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice_id' => $newInvoice->id,
        ], 201); // 201 Created status code
    }

    /**
     * Display the specified resource.
     * Selecting an invoice should hit this endpoint.
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
    public function update(InvoiceRequest $request, Invoice $invoice)
    {
        // if updating a draft, overwrite everything and existing client and addresses

        // record current_client_id and address ids
        // if saving a pending, attempt to find first client match and attempt to find first address match of request info. call them "singleton_client_id" etc.
        // if match(es) is(are) found, adjust invoice to point to existing records. No update necessary. this may be the currently referenced record
        //     else update current client and/or address records
        // save invoice to db
        // if singleton_id != current_id
        // if old client id has no invoices, delete. Same for addresses.
    }

    /**
     * Remove the specified resource from storage.
     * "Delete" Button hits this endpoint.
     * Deletion cascade is defined in deleting event of Invoice
     */
    public function destroy(Invoice $invoice)
    {
        // Invoice's "deleting" event will delete clients and addresses when aren't in any invoices.
        $invoice->delete(); // 204 = no data
        return response()->noContent();
    }
}
