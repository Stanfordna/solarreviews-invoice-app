<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\LineItem;

class LineItemTableSeeder extends Seeder
{
    /**
     * Extract client fields from seed data json
     */
    public function run(): void
    {
        $json = File::get(database_path('data/seed_data.json'));
        $data = json_decode($json);

        foreach ($data as $invoice) {

            foreach ($invoice->line_items as $line_item) {
                LineItem::firstOrCreate([
                    'invoice_id' => $invoice->id,
                    'name' => $line_item->name,
                    'quantity' => $line_item->quantity,
                    'price_unit_cents' => $line_item->price_unit_cents,
                    'price_total_cents' => $line_item->price_total_cents
                ]);
            }
        }
    }
}
