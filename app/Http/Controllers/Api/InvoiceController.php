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

        // 1. Validate the incoming request data
        $validatedData = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
        ]);

        // 2. Create a new Post instance
        $post = new Invoice();
        $post->title = $validatedData['title'];
        $post->content = $validatedData['content'];
        // You might also set a user_id here if posts belong to users
        // $post->user_id = auth()->id(); 

        // 3. Save the Post to the database
        $post->save();

        // 4. Redirect the user or return a response
        return redirect()->route('posts.show', $post->id)->with('success', 'Post created successfully!');
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
