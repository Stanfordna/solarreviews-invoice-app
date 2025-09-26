<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use App\Models\Client;
use App\Models\Address;
use App\Models\Invoice;
use Tests\TestCase;

class InvoiceCrudTest extends TestCase
{
    use RefreshDatabase;
    protected $seed = true;
    protected int $totalInvoiceRecords = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totalInvoiceRecords = Invoice::count();
    }


    protected function createInvoicesRequestData(string $id = "", string $status = "draft"): array {
        $data = [
            'id' => $id,
            'issue_date' => date('Y-m-d'),
            'description' => 'Test invoice description',
            'payment_terms' => 30,
            'client_name' => 'Ricky Bobby',
            'client_email' => 'el.diablo@gmail.com',
            'status' => $status,
            'sender_address' => [
                'street' => '930 Acoma Street Unit 316',
                'city' => 'Denver',
                'postal_code' => '80204',
                'country' => 'United States of America',
            ],
            'client_address' => [
                'street' => '17240 Connor Quay Ct',
                'city' => 'Cornelius',
                'postal_code' => '28031',
                'country' => 'United States of America',
            ],
            'line_items' => [
                [
                    'name' => 'apple',
                    'quantity' => 1,
                    'price_unit_cents' => 5000,
                    'price_total_cents' => 5000,
                ],
                [
                    'name' => 'banana',
                    'quantity' => 3,
                    'price_unit_cents' => 4000,
                    'price_total_cents' => 12000,
                ],
                [
                    'name' => 'carrot',
                    'quantity' => 2,
                    'price_unit_cents' => 2500,
                    'price_total_cents' => 5000,
                ],
            ],
        ];
        return $data;
    }

    /**
     * Test: index
     *
     * Purpose:
     * - Verify the /api/invoices endpoint returns a collection of invoices
     *   and includes expected top-level meta (count, api_version) when using the
     *   InvoicesResourceCollection.
     *
     * Assertions:
     * - HTTP 200
     * - Each invoice object matches InvoiceResource shape (id, issue_date, client_name, line_items, total_cents, etc.)
     * - JSON has "data" array with same number of objects as Invoice records in the database.
     * - JSON meta contains 'count' equal to the expected count of invoices.
     * - If no invoices exist expect empty data array and meta count 0
     */
    public function test_invoices_index_returns_expected_json_structure_and_code_200(): void {
        $response = $this->get(route('invoices.index'));
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'issue_date',
                        'due_date',
                        'description',
                        'payment_terms',
                        'client_name',
                        'client_email',
                        'status',
                        'sender_address' => [
                            'street',
                            'city',
                            'postal_code',
                            'country'
                        ],
                        'client_address' => [
                            'street',
                            'city',
                            'postal_code',
                            'country'
                        ],
                        'line_items' => [
                            '*' => [
                                'name',
                                'quantity',
                                'price_unit_cents',
                                'price_total_cents'
                            ],
                        ],
                        'total_cents'
                    ],
                ]
            ]);
    }

    // uses $totalInvoiceRecords from setUp()
    public function test_invoices_index_returns_appropriate_number_of_invoices_with_count_in_metadata(): void {
        $response = $this->get(route('invoices.index'));
        $response
            ->assertStatus(200)
            ->assertJsonCount($this->totalInvoiceRecords, 'data')
            ->assertJson([
                'meta' => [
                    'count' => $this->totalInvoiceRecords
                ],
            ]);
    }

    public function test_does_invoices_index_break_if_there_are_no_invoices(): void {
        // empty table to test edge case of empty database
        Invoice::truncate();
        $response = $this->get(route('invoices.index'));
        $response
            ->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJson([
                'meta' => [
                    'count' => 0
                ],
            ]);
    }

    /**
     * Test: POST /invoices (InvoiceController::store())
     *
     * Purpose: TODO: clean this excessive mess up
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
    public function test_post_stores_new_invoice_with_a_well_formed_id()
    {
        $response = $this->post(route('invoices.store', $this->createInvoicesRequestData()));
        $responseData = $response->decodeResponseJson();
        $response->assertStatus(201);
        $this->assertMatchesRegularExpression('/^[A-Z]{2}[0-9]{4}$/', $responseData["invoice_id"]);

    }

    /**
     * Test: GET /invoices/{id} (InvoiceController::show()) TODO: add this to other descriptions
     *
     * Purpose:
     * - Verify retrieving a single invoice returns the invoice formatted by InvoiceResource.
     * 
     * Assertions:
     * - HTTP 200
     * - JSON "data" contains an object with fields: id, issue_date, due_date, description,
     *   payment_terms, client_name, client_email, status, sender_address, client_address, line_items, total_cents
     * - Date formats and numeric fields match expected formats/values
     * - HTTP 404 on attempt to show non-existent invoice
     * 
     */
    public function test_get_invoice_returns_expected_json_structure_and_code_200(): void {
        $randomInvoice = Invoice::inRandomOrder()->first();
        $response = $this->get(route('invoices.show', $randomInvoice));
        $response->assertStatus(200);

    }
    public function test_get_invoice_id_that_does_not_exist_returns_404(): void {

        $nonExistentInvoiceId = 'AAA000'; // this ID won't exist because it has three letters
        $response = $this->get(route('invoices.show', $nonExistentInvoiceId));
        $response->assertStatus(404);
    }

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
     * Assertions:
     * - HTTP 204 (no content) or appropriate success status
     * - Invoice row no longer exists in DB
     * - If the invoice deletion logic deletes orphaned client/address records, assert they are deleted
     * - If client/address are referenced by other invoices, they must remain
     * - HTTP 404 on attempt to delete non-existent invoice
     * 
     */
    public function test_delete_invoice_but_not_client_because_client_has_other_invoices(): void {

        $clientWithMultipleInvoices = Client::has('invoices', '>', 1)->first();
        $clientId = $clientWithMultipleInvoices->id;
        $invoiceThatBelongsToThatClient = $clientWithMultipleInvoices->invoices()->first();
        $invoiceId = $invoiceThatBelongsToThatClient->id;

        $response = $this->delete(route('invoices.destroy', $invoiceThatBelongsToThatClient));
        $response->assertStatus(204); // 204 = "no content"
        $this->assertDatabaseHas('clients', ['id' => $clientId]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
    }

    public function test_delete_invoice_and_client_because_client_has_no_other_invoices(): void {

        $clientWithOneInvoice = Client::has('invoices', '=', 1)->first();
        $clientId = $clientWithOneInvoice->id;
        $invoiceThatBelongsToThatClient = $clientWithOneInvoice->invoices()->first();
        $invoiceId = $invoiceThatBelongsToThatClient->id;

        $response = $this->delete(route('invoices.destroy', $invoiceThatBelongsToThatClient));
        $response->assertStatus(204); // 204 = "no content"
        $this->assertDatabaseMissing('clients', ['id' => $clientId]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
    }
    
    public function test_delete_invoice_but_not_address_because_address_is_on_other_invoices(): void {

        $addressOnMultipleInvoices = Address::has('clientInvoices', '>', 1)->first();
        $addressId = $addressOnMultipleInvoices->id;
        $invoiceWithThatAddress = $addressOnMultipleInvoices->clientInvoices()->first();
        $invoiceId = $invoiceWithThatAddress->id;

        $response = $this->delete(route('invoices.destroy', $invoiceWithThatAddress));
        $response->assertStatus(204); // 204 = "no content"
        $this->assertDatabaseHas('addresses', ['id' => $addressId]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
    }
    
    public function test_delete_invoice_and_address_because_address_is_not_on_other_invoices(): void {

        $addressOnOneInvoice = Address::has('clientInvoices', '=', 1)->first();
        $addressId = $addressOnOneInvoice->id;
        $invoiceWithThatAddress = $addressOnOneInvoice->clientInvoices()->first();
        $invoiceId = $invoiceWithThatAddress->id;

        $response = $this->delete(route('invoices.destroy', $invoiceId));
        $response->assertStatus(204); // 204 = "no content"
        $this->assertDatabaseMissing('addresses', ['id' => $addressId]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoiceId]);
    }

    public function test_delete_invoice_id_that_does_not_exist_returns_404(): void {

        $nonExistentInvoiceId = 'AAA000'; // this ID won't exist because it has three letters
        $response = $this->delete(route('invoices.destroy', $nonExistentInvoiceId));
        $response->assertStatus(404);
    }
}
