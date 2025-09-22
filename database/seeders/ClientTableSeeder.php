<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\Client;

class ClientTableSeeder extends Seeder
{
    /**
     * Extract client fields from seed data json
     */
    public function run(): void
    {
        $json = File::get(database_path('data/seed_data.json'));
        $data = json_decode($json);

        foreach ($data as $invoice) {
            Client::firstOrCreate([
                'full_name' => $invoice->client_name,
                'email' => $invoice->client_email
            ]);
        }
    }
}
