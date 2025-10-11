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
use Illuminate\Support\Facades\Log;

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
        Log::info("Incoming Request:\n" . json_encode($data, JSON_PRETTY_PRINT));

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
            if (isset($lineItem['price_unit_cents']) && isset($lineItem['quantity'])) {
                $lineItem['price_total_cents'] = $lineItem['price_unit_cents'] * $lineItem['quantity'];
            } else {
                $lineItem['price_unit_cents'] = 0;
                $lineItem['quantity'] = 0;
                $lineItem['price_total_cents'] = 0;
            }
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
        if (!isset($data['line_items'])) $data['line_items'] = [];
        foreach ($data['line_items'] as $lineItem) {
            // for a post request we are making all new line items, not updating or removing existing ones.
            LineItem::create([
                'invoice_id' => $newInvoice->id,
                'name' => ($lineItem['name'] ?? ""),
                'quantity' => ($lineItem['quantity'] ?? null),
                'price_unit_cents' => ($lineItem['price_unit_cents'] ?? null),
                'price_total_cents' => (($lineItem['price_unit_cents'] ?? 0) * ($lineItem['quantity'] ?? 0))
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
        $data = $request->all();
        Log::info("\n\nIncoming PUT Request:\n" . json_encode($data, JSON_PRETTY_PRINT));

        if (($invoice->status == "pending" || $invoice->status == "paid") && $data['status'] == 'draft') {
            return response()->json([
                'message' => 'Invoice with current status "Pending" or "Paid" cannot be changed to "Draft".',
                'invoice_id' => $invoice->id
            ], 422); // 201 Created status code
        }

        if (!isset($data['issue_date']) || !isset($data['payment_terms'])) {
            $dueDate = null;
        } else {
            $dueDate = date('Y-m-d', strtotime("{$data['issue_date']} +{$data['payment_terms']} days"));
        }

        // first update values belonging to the invoices table, not foreign relations
        $invoice->issue_date = $data['issue_date'] ?? "";
        $invoice->due_date = $dueDate ?? "";
        $invoice->description = $data['description'] ?? "";
        $invoice->payment_terms = $data['payment_terms'] ?? "";
        $invoice->status = $data['status'] ?? "";

        // get invoice client and addresses
        $currentClient = $invoice->client;
        $currentClientAddress = $invoice->clientAddress;
        $currentSenderAddress = $invoice->senderAddress;


        // Consider changed if any relevant field differs (use OR, not AND)
        $clientChanged = (
            ($invoice->client->full_name ?? "") !== ($data['client_name'] ?? "") ||
            ($invoice->client->email ?? "") !== ($data['client_email'] ?? "")
        );

        $clientAddressChanged = (
            ($invoice->clientAddress->street ?? "") !== ($data['client_address']['street'] ?? "") ||
            ($invoice->clientAddress->city ?? "") !== ($data['client_address']['city'] ?? "") ||
            ($invoice->clientAddress->postal_code ?? "") !== ($data['client_address']['postal_code'] ?? "") ||
            ($invoice->clientAddress->country ?? "") !== ($data['client_address']['country'] ?? "")
        );

        $senderAddressChanged = (
            ($invoice->senderAddress->street ?? "") !== ($data['sender_address']['street'] ?? "") ||
            ($invoice->senderAddress->city ?? "") !== ($data['sender_address']['city'] ?? "") ||
            ($invoice->senderAddress->postal_code ?? "") !== ($data['sender_address']['postal_code'] ?? "") ||
            ($invoice->senderAddress->country ?? "") !== ($data['sender_address']['country'] ?? "")
        );

        // Clients/Addresses that changed but already exist elsewhere should be referenced.
        $existingClient = 
            Client::where('full_name', ($data['client_name'] ?? ""))
            ->where('email', ($data['client_email'] ?? ""))
            ->where('id', '!=', $invoice->client_id)
            ->first();
        $updatedClientExists = $existingClient instanceof Client;

        // look for an existing address that matches the incoming client address (excluding current)
        $existingClientAddress = 
            Address::where('street', ($data['client_address']['street'] ?? ""))
            ->where('city', ($data['client_address']['city'] ?? ""))
            ->where('postal_code', ($data['client_address']['postal_code'] ?? ""))
            ->where('country', ($data['client_address']['country'] ?? ""))
            ->where('id', '!=', $invoice->client_address_id)
            ->first();
        $updatedClientAddressExists = $existingClientAddress instanceof Address;

        // look for an existing address that matches the incoming sender address (excluding current)
        $existingSenderAddress = 
            Address::where('street', ($data['sender_address']['street'] ?? ""))
            ->where('city', ($data['sender_address']['city'] ?? ""))
            ->where('postal_code', ($data['sender_address']['postal_code'] ?? ""))
            ->where('country', ($data['sender_address']['country'] ?? ""))
            ->where('id', '!=', $invoice->sender_address_id)
            ->first();
        $updatedSenderAddressExists = $existingSenderAddress instanceof Address;

        // Clients/Addresses that changed, do not exist elsewhere, and appear on another invoice need to be created.
        $createNewClient = (
            isSet($currentClient->invoices) && $currentClient->invoices()->count() > 1 && $clientChanged && !$updatedClientExists
        );
        $createNewClientAddress = (
            isSet($currentClientAddress->clientInvoices) && $currentClientAddress->clientInvoices()->count() > 1 && $clientAddressChanged && !$updatedClientAddressExists
        );
        $createNewSenderAddress = (
            isSet($currentSenderAddress->senderInvoices) && $currentSenderAddress->senderInvoices()->count() > 1 && $senderAddressChanged && !$updatedSenderAddressExists
        );
        //  Clients/Addresses that changed, do not exist elsewhere, but appear on no other invoices need to be updated.
        $updateCurrentClient = (
            isSet($currentClient->invoices) && $currentClient->invoices()->count() == 1 && $clientChanged && !$updatedClientExists
        );
        $updateCurrentClientAddress = (
            isSet($currentClientAddress->clientInvoices) && $currentClientAddress->clientInvoices()->count() == 1 && $clientAddressChanged && !$updatedClientAddressExists
        );
        $updateCurrentSenderAddress = (
            isSet($currentSenderAddress->senderInvoices) && $currentSenderAddress->senderInvoices()->count() == 1 && $senderAddressChanged && !$updatedSenderAddressExists
        );
        // Clients/Addresses that no longer appear on an invoice should be removed.
        $deleteCurrentClient = (
            isSet($currentClient->invoices) && $currentClient->invoices()->count() == 1 && $updatedClientExists
        );
        $deleteCurrentClientAddress = (
            isSet($currentClientAddress->clientInvoices) && $currentClientAddress->clientInvoices()->count() == 1 && $updatedClientAddressExists
        );
        $deleteCurrentSenderAddress = (
            isSet($currentSenderAddress->senderInvoices) && $currentSenderAddress->senderInvoices()->count() == 1 && $updatedSenderAddressExists
        );

        // dump($createNewClient);
        // dump($createNewClientAddress);
        // dump($createNewSenderAddress);
        // dump($updateCurrentClient);
        // dump($updateCurrentClientAddress);
        // dump($updateCurrentSenderAddress);
        // dump($deleteCurrentClient);
        // dump($deleteCurrentClientAddress);
        // dump($deleteCurrentSenderAddress);
        // dump($updatedClientExists);
        // dump($updatedClientAddressExists);
        // dump($updatedSenderAddressExists);

        
        if ($updatedClientExists) {
            $invoice->client()->associate($existingClient);
        }
        if ($updatedClientAddressExists) {
            $invoice->clientAddress()->associate($existingClientAddress);
        }
        if ($updatedSenderAddressExists) {
            $invoice->senderAddress()->associate($existingSenderAddress);
        }

        if ($createNewClient) {
            $newClient = Client::create([
                'full_name' => ($data['client_name'] ?? ""),
                'email' => ($data['client_email'] ?? "")
            ]);
            // associate the newly created client to the invoice
            $invoice->client()->associate($newClient);
            Log::info("\nCreated CLIENT:\n" . json_encode($newClient, JSON_PRETTY_PRINT));
        }
        if ($createNewClientAddress) {
            $newClientAddress = Address::create([
                'street' => ($data['client_address']['street'] ?? ""),
                'city' => ($data['client_address']['city'] ?? ""),
                'postal_code' => ($data['client_address']['postal_code'] ?? ""),
                'country' => ($data['client_address']['country'] ?? "")
            ]);
            // associate as the invoice's client address
            $invoice->clientAddress()->associate($newClientAddress);
            Log::info("\nCreated client ADDRESS:\n" . json_encode($newClientAddress, JSON_PRETTY_PRINT));
        }

        if ($createNewSenderAddress) {
            $newSenderAddress = Address::create([
                'street' => ($data['sender_address']['street'] ?? ""),
                'city' => ($data['sender_address']['city'] ?? ""),
                'postal_code' => ($data['sender_address']['postal_code'] ?? ""),
                'country' => ($data['sender_address']['country'] ?? "")
            ]);
            // associate the newly created sender address
            $invoice->senderAddress()->associate($newSenderAddress);
            Log::info("\nCreated sender ADDRESS:\n" . json_encode($newSenderAddress, JSON_PRETTY_PRINT));
        }

        if ($updateCurrentClient) {
            $invoice->client->full_name = ($data['client_name'] ?? "");
            $invoice->client->email = ($data['client_email'] ?? "");
            $invoice->client->save();
            Log::info("\nUpdating CLIENT:\n{$invoice->client->full_name}\n{$invoice->client->email}");
        }
        if ($updateCurrentClientAddress) {
            $invoice->clientAddress->street = ($data['client_address']['street'] ?? "");
            $invoice->clientAddress->city = ($data['client_address']['city'] ?? "");
            $invoice->clientAddress->postal_code = ($data['client_address']['postal_code'] ?? "");
            $invoice->clientAddress->country = ($data['client_address']['country'] ?? "");
            $invoice->clientAddress->save();
            Log::info("\nUpdating client ADDRESS:\n{$invoice->clientAddress->street}\n" .
                      "{$invoice->clientAddress->city}\n{$invoice->clientAddress->postal_code}\n" .
                      "{$invoice->clientAddress->country}\n");
        }
        if ($updateCurrentSenderAddress) {
            $invoice->senderAddress->street = ($data['sender_address']['street'] ?? "");
            $invoice->senderAddress->city = ($data['sender_address']['city'] ?? "");
            $invoice->senderAddress->postal_code = ($data['sender_address']['postal_code'] ?? "");
            $invoice->senderAddress->country = ($data['sender_address']['country'] ?? "");
            $invoice->senderAddress->save();
            Log::info("\nUpdating sender ADDRESS:\n{$invoice->senderAddress->street}\n" .
                      "{$invoice->senderAddress->city}\n{$invoice->senderAddress->postal_code}\n" .
                      "{$invoice->senderAddress->country}\n");
        }

        if ($deleteCurrentClient) {
            Log::info("\nDeleting client {$currentClient->id}");
            $currentClient->delete();
        }
        if ($deleteCurrentClientAddress) {
            Log::info("\nDeleting address {$currentClientAddress->id}");
            $currentClientAddress->delete();
        }
        if ($deleteCurrentSenderAddress) {
            Log::info("\nDeleting address {$currentSenderAddress->id}");
            $currentSenderAddress->delete();
        }
        // overwrite all line items - just remove and create new ones
        $invoice->lineItems()->delete();

        $invoiceTotal = 0;
        // create line items using new invoice id for foreign key
        if (!isset($data['line_items'])) $data['line_items'] = [];
        foreach ($data['line_items'] as $lineItem) {
            // for a post request we are making all new line items, not updating or removing existing ones.
            LineItem::create([
                'invoice_id' => $invoice->id,
                'name' => ($lineItem['name'] ?? ""),
                'quantity' => ($lineItem['quantity'] ?? null),
                'price_unit_cents' => ($lineItem['price_unit_cents'] ?? null),
                'price_total_cents' => ($lineItem['price_unit_cents'] ?? 0) * ($lineItem['quantity'] ?? 0)
            ]);
            Log::info("Created line item {$lineItem['name']}");
            
            if (isset($lineItem['price_unit_cents']) && isset($lineItem['quantity'])) {
                $lineItem['price_total_cents'] = $lineItem['price_unit_cents'] * $lineItem['quantity'];
            } else {
                $lineItem['price_total_cents'] = 0;
            }
            $invoiceTotal += $lineItem['price_total_cents'];
        }
        $invoice->total_cents = $invoiceTotal ?? 0;

        $invoice->save();
        // Return the invoice ID in the response

        return response()->json([
            'message' => 'Invoice successfully updated',
            'invoice_id' => $invoice->id
        ], 201); // 201 Created status code
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
