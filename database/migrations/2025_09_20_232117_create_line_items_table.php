<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->references('id')->on('invoices')
                  ->onDelete('cascade');
            $table->string('name')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedInteger('price_unit_cents')->nullable();
            $table->unsignedInteger('price_total_cents')->nullable();
            $table->timestamps();
            $table->unique(['invoice_id', 'name']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('line_items');
    }
};
