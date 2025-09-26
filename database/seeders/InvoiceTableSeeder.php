<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\Client;
use App\Models\Address;
use App\Models\Invoice;

class InvoiceTableSeeder extends Seeder
{
    /**
     * Extract client fields from seed data json
     */
    public function run(): void
    {
        $json = File::get(database_path('data/seed_data.json'));
        $data = json_decode($json);

        foreach ($data as $invoice) {
            // client seeder has already run to fill db with clients
            $client = 
            Client::where('full_name', $invoice->client_name)
                ->where('email', $invoice->client_email)->first();

            // address seeder has already run to fill db with addresses
            $sender_address_id =
            Address::where('street', $invoice->sender_address->street)
                ->where('city', $invoice->sender_address->city)
                ->where('postal_code', $invoice->sender_address->postal_code)
                ->where('country', $invoice->sender_address->country)->first()->id;

            // address seeder has already run to fill db with addresses
            $client_address_id =
            Address::where('street', $invoice->client_address->street)
                ->where('city', $invoice->client_address->city)
                ->where('postal_code', $invoice->client_address->postal_code)
                ->where('country', $invoice->client_address->country)->first()->id;

            Invoice::create([
                'id' => $invoice->id,
                'issue_date' => $invoice->issue_date,
                'due_date' => $invoice->due_date,
                'description' => $invoice->description,
                'payment_terms' => $invoice->payment_terms,
                'client_id' => $client->id,
                'status' => $invoice->status,
                'sender_address_id' => $sender_address_id,
                'client_address_id' => $client_address_id,
                'total_cents' => $invoice->total_cents
            ]);
        }
    }
}
