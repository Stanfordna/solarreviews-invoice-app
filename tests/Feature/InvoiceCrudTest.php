<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;
use App\Models\Client;
use App\Models\Invoice;
use Tests\TestCase;

class InvoiceCrudTest extends TestCase
{
    use RefreshDatabase;
    protected $seed = true;
    /**
     * Delete an invoice but don't delete the client
     */
    public function test_delete_invoice_but_not_its_client_owner(): void
    {
        $clientWithMultipleInvoices = Client::has('invoices', '>', 1)->first();
        $clientId = $clientWithMultipleInvoices->id;
        $invoiceThatBelongsToThatClient = $clientWithMultipleInvoices->invoices()->first();
        Log::info("Deleting Invoice $invoiceThatBelongsToThatClient belonging to Client $clientId.");

        $response = $this->delete(route('invoices.destroy', $invoiceThatBelongsToThatClient));
        $response->assertStatus(200);
        $this->assertDatabaseHas('clients', ['id' => $clientId]);
    }

    /**
     * Delete an invoice and delete the client because they don't have any invoices anymore
     */
    public function test_delete_invoice_and_its_client_owner(): void
    {
        $clientWithOneInvoice = Client::has('invoices', '=', 1)->first();
        $clientId = $clientWithOneInvoice->id;
        $invoiceThatBelongsToThatClient = $clientWithOneInvoice->invoices()->first();
        Log::info("Deleting Invoice $invoiceThatBelongsToThatClient belonging to Client $clientId, who only has one invoice.");
        $response = $this->delete(route('invoices.destroy', $invoiceThatBelongsToThatClient));
        $response->assertStatus(200);
        $this->assertDatabaseMissing('clients', ['id' => $clientId]);
    }
}
