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
            $client_id = Client::where('full_name', $invoice->client_name)
                ->where('email', $invoice->client_email)->first();
            if ( is_null($client_id)) {
                echo "FUCK!!";
                dd($invoice);
            }
            $client_id = $client_id->id;
            echo "CLIENT ID\n" . $client_id;

            $_sender_address = $invoice->sender_address;
            $_client_address = $invoice->client_address;

            $sender_address_id = Address::where('street', $_sender_address->street)
                ->where('city', $_sender_address->city)
                ->where('postal_code', $_sender_address->postal_code)
                ->where('country', $_sender_address->country)->first()->id;

            $client_address_id = Address::where('street', $_client_address->street)
                ->where('city', $_client_address->city)
                ->where('postal_code', $_client_address->postal_code)
                ->where('country', $_client_address->country)->first()->id;

            Invoice::create([
                'id' => $invoice->id,
                'issue_date' => $invoice->issue_date,
                'due_date' => $invoice->due_date,
                'description' => $invoice->description,
                'payment_terms' => $invoice->payment_terms,
                'client_id' => $client_id,
                'status' => $invoice->status,
                'sender_address_id' => $sender_address_id,
                'client_address_id' => $client_address_id,
                'total_cents' => $invoice->total_cents
            ]);
        }
    }
}
