<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Invoice;
use App\Models\Client;
use App\Models\Address;

class InvoiceIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_custom_id_has_expected_format_and_is_unique()
    {
        // Create minimal related models
        $client = Client::create(['full_name' => 'Test Client', 'email' => 'test@example.com']);
        $senderAddress = Address::create(['street' => '1 Test St', 'city' => 'Testville', 'postal_code' => '12345', 'country' => 'Testland']);
        $clientAddress = Address::create(['street' => '2 Client Rd', 'city' => 'Clienttown', 'postal_code' => '54321', 'country' => 'Clientland']);

        // Create first invoice
        $invoice1 = Invoice::create([
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'client_id' => $client->id,
            'sender_address_id' => $senderAddress->id,
            'client_address_id' => $clientAddress->id,
            'total_cents' => 1000,
        ]);

        $this->assertMatchesRegularExpression('/^[A-Z]{2}\d{4}$/', $invoice1->id);

        // Create second invoice and ensure different id
        $invoice2 = Invoice::create([
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'client_id' => $client->id,
            'sender_address_id' => $senderAddress->id,
            'client_address_id' => $clientAddress->id,
            'total_cents' => 2000,
        ]);

        $this->assertMatchesRegularExpression('/^[A-Z]{2}\d{4}$/', $invoice2->id);
        $this->assertNotEquals($invoice1->id, $invoice2->id, 'Expected two generated invoice ids to be unique');
    }
}
