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
        // if saving a new record with status=draft, create new client, two new addresses, and invoice

        // if saving a new record with status=pending, attempt to find a client/addresses and keep id handy.
        // for each: if not found, create record and keep ids handy
        // create invoice with ids
        // include id in response


        // Return the invoice ID in the response
        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice_id' => "AA0000",
            // 'invoice_id' => $invoice->id,
        ], 201); // 201 Created status code
        // // Create minimal related models
        // $client = Client::create(['full_name' => 'Ricky Bobby', 'email' => 'el.diablo.loco@gmail.com']);
        // $senderAddress = Address::create(['street' => '930 Acoma Street Unit 316', 'city' => 'Denver', 'postal_code' => '80204', 'country' => 'United States of America']);
        // $clientAddress = Address::create(['street' => '17240 Connor Quay Ct', 'city' => 'Cornelius', 'postal_code' => '28031', 'country' => 'United States of America']);

        // // Create first invoice
        // $invoice1 = Invoice::create([
        //     'issue_date' => now()->toDateString(),
        //     'due_date' => now()->addDays(7)->toDateString(),
        //     'client_id' => $client->id,
        //     'sender_address_id' => $senderAddress->id,
        //     'client_address_id' => $clientAddress->id,
        //     'total_cents' => 1000,

        
        // $this->assertMatchesRegularExpression('/^[A-Z]{2}\d{4}$/', $invoice2->id);
        // $this->assertNotEquals($invoice1->id, $invoice2->id, 'Expected two generated invoice ids to be unique');

        // // 2. Create a new Post instance
        // $post = new Invoice();
        // $post->title = $validatedData['title'];
        // $post->content = $validatedData['content'];
        // // You might also set a user_id here if posts belong to users
        // // $post->user_id = auth()->id(); 

        // // 3. Save the Post to the database
        // $post->save();

        // // 4. Redirect the user or return a response
        // return redirect()->route('posts.show', $post->id)->with('success', 'Post created successfully!');
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
