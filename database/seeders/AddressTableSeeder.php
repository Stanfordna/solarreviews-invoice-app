<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\Address;

class AddressTableSeeder extends Seeder
{
    /**
     * Extract client fields from seed data json
     */
    public function run(): void
    {
        $json = File::get(database_path('data/seed_data.json'));
        $data = json_decode($json);

        foreach ($data as $invoice) {
            $_sender_address = $invoice->sender_address;
            $_client_address = $invoice->client_address;
            Address::firstOrCreate([
                'street' => $_sender_address->street,
                'city' => $_sender_address->city,
                'postal_code' => $_sender_address->postal_code,
                'country' => $_sender_address->country
            ]);
            Address::firstOrCreate([
                'street' => $_client_address->street,
                'city' => $_client_address->city,
                'postal_code' => $_client_address->postal_code,
                'country' => $_client_address->country
            ]);
        }
    }
}
