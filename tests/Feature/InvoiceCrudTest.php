<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use App\Models\Client;
use App\Models\Address;
use Tests\TestCase;

class InvoiceCrudTest extends TestCase
{
    use RefreshDatabase;
    protected $seed = true;


    /**
     * Test: index
     *
     * Purpose:
     * - Verify the invoices listing endpoint returns a collection of invoices
     *   and includes expected top-level meta (count, api_version) when using the
     *   InvoicesResourceCollection.
     *
     * Minimal setup:
     * - Seed/create 1-3 invoices (with related client, addresses, and line items).
     * - Eager-load relations in controller or ensure resource handles missing relations.
     *
     * Assertions:
     * - HTTP 200
     * - JSON has "data" array
     * - JSON meta contains 'count' equal to number of created invoices
     * - Each item matches InvoiceResource shape (id, issue_date, client_name, line_items, total_cents, etc.)
     *
     * Edge cases:
     * - No invoices -> expect empty data array and meta count 0
     * - Invoices with missing relations -> resource should still return safe defaults (null/empty values)
     */


    /**
     * Test: store
     *
     * Purpose:
     * - Verify creating an invoice via the API persists a new invoice and related
     *   objects follow the expected behavior (client/addresses created or attached).
     *
     * Minimal setup:
     * - Prepare valid payload that matches your controller/store validation rules.
     *   Include required fields (issue_date, due_date, client details, sender & client addresses,
     *   line_items, total_cents, status/payment_terms as appropriate).
     *
     * Assertions:
     * - Appropriate HTTP status (201 Created or 200 depending on controller)
     * - Database contains the created invoice row with expected values
     * - Related client/address rows exist (if controller creates them)
     * - Response JSON contains invoice id and matches InvoiceResource shape
     *
     * Edge cases:
     * - Missing required fields -> expect validation error (422)
     * - Creating with status=draft vs status=pending -> different creation paths (new vs existing client)
     */


    /**
     * Test: show
     *
     * Purpose:
     * - Verify retrieving a single invoice returns the invoice formatted by InvoiceResource.
     *
     * Minimal setup:
     * - Create an invoice with related client, addresses and line items.
     *
     * Assertions:
     * - HTTP 200
     * - JSON "data" contains an object with fields: id, issue_date, due_date, description,
     *   payment_terms, client_name, client_email, status, sender_address, client_address, line_items, total_cents
     * - Date formats and numeric fields match expected formats/values
     *
     * Edge cases:
     * - Requesting a non-existent invoice -> expect 404
     * - Invoice missing optional relations -> fields should be null/empty but response still valid
     */


    /**
     * Test: update
     *
     * Purpose:
     * - Verify updating an invoice changes persisted fields and handles related records correctly.
     *
     * Minimal setup:
     * - Create an invoice (status draft or pending as required).
     * - Prepare an update payload that modifies simple attributes and possibly related client/address info.
     *
     * Assertions:
     * - HTTP 200 (or 204 depending on implementation)
     * - Database reflects updated invoice fields
     * - If client/address matching logic exists, ensure appropriate attach/update behavior occurred
     * - Response (if any) contains the updated InvoiceResource or appropriate status
     *
     * Edge cases:
     * - Attempt to update immutable fields (like id) -> should be rejected or ignored
     * - Update causing client/address to change -> ensure old client/address deleted only when no other invoices reference them
     */


    /**
     * Tests: destroy
     *
     * Purpose:
     * - Verify deleting an invoice removes the invoice and triggers the model's deleting
     *   event behavior (cascade or cleanup of clients/addresses when appropriate).
     *
     * Minimal setup:
     * - Database will have been seeded by a user with one invoice and a user with two invoices.
     *   The sender addresses are all the same and the second client has the same address in both invoices. 
     *
     * Assertions:
     * - HTTP 204 (no content) or appropriate success status
     * - Invoice row no longer exists in DB
     * - If the invoice deletion logic deletes orphaned client/address records, assert they are deleted
     * - If client/address are referenced by other invoices, they must remain
     * - HTTP 404 on attempt to delete non-existent invoice
     * 
     */
    public function test_delete_invoice_but_not_its_client_owner(): void {

        $clientWithMultipleInvoices = Client::has('invoices', '>', 1)->first();
        $clientId = $clientWithMultipleInvoices->id;
        $invoiceThatBelongsToThatClient = $clientWithMultipleInvoices->invoices()->first();
        $invoiceId = $invoiceThatBelongsToThatClient->id;
        Log::info("Deleting Invoice $invoiceId belonging to Client $clientId.");

        $response = $this->delete(route('invoices.destroy', $invoiceThatBelongsToThatClient));
        $response->assertStatus(204); // 204 = "no content"
        $this->assertDatabaseHas('clients', ['id' => $clientId]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
    }

    public function test_delete_invoice_and_its_client_owner(): void {

        $clientWithOneInvoice = Client::has('invoices', '=', 1)->first();
        $clientId = $clientWithOneInvoice->id;
        $invoiceThatBelongsToThatClient = $clientWithOneInvoice->invoices()->first();
        $invoiceId = $invoiceThatBelongsToThatClient->id;
        Log::info("Deleting Invoice $invoiceId belonging to Client $clientId, who only has one invoice.");

        $response = $this->delete(route('invoices.destroy', $invoiceThatBelongsToThatClient));
        $response->assertStatus(204); // 204 = "no content"
        $this->assertDatabaseMissing('clients', ['id' => $clientId]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
    }
    
    public function test_delete_invoice_but_not_its_client_address(): void {

        $addressOnMultipleInvoices = Address::has('clientInvoices', '>', 1)->first();
        $addressId = $addressOnMultipleInvoices->id;
        $invoiceWithThatAddress = $addressOnMultipleInvoices->clientInvoices()->first();
        $invoiceId = $invoiceWithThatAddress->id;
        Log::info("Deleting Invoice $invoiceId belonging to Client $addressId.");

        $response = $this->delete(route('invoices.destroy', $invoiceWithThatAddress));
        $response->assertStatus(204); // 204 = "no content"
        $this->assertDatabaseHas('addresses', ['id' => $addressId]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
    }
    
    public function test_delete_invoice_and_its_client_address(): void {

        $addressOnOneInvoice = Address::has('clientInvoices', '=', 1)->first();
        $addressId = $addressOnOneInvoice->id;
        $invoiceWithThatAddress = $addressOnOneInvoice->clientInvoices()->first();
        $invoiceId = $invoiceWithThatAddress->id;
        Log::info("Deleting Invoice $invoiceId belonging to Client $addressId, who only has one invoice.");

        $response = $this->delete(route('invoices.destroy', $invoiceId));
        $response->assertStatus(204); // 204 = "no content"
        $this->assertDatabaseMissing('addresses', ['id' => $addressId]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
    }

    public function test_delete_missing_id_returns_404(): void {

        $nonExistentPostId = 'AAA000'; // this ID won't exist because it has three letters
        $response = $this->delete(route('invoices.destroy', $nonExistentPostId));
        $response->assertStatus(404);
    }
}
