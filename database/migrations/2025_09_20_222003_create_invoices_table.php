<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration to create Invoices table
     * 
     * In the "deleting" event of an invoice, the invoice's addresses and client entities are
     * deleted if they will no longer be present in any other invoices. onDelete('set null') sets
     * those foreign keys to null in the Invoice record if the client and addresses get deleted.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->string('id', 6)->primary(); // two random uppercase followed by 4 random digits
            $table->date('issue_date')->default(now());
            $table->date('due_date');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('payment_terms')->default(1);
            $table->foreignId('client_id')->nullable()
                  ->constrained('clients')->onDelete('set null');
            $table->string('status')->default('draft'); // draft, pending, paid
            $table->foreignId('sender_address_id')->nullable()
                  ->constrained('addresses')->onDelete('set null');
            $table->foreignId('client_address_id')->nullable()
                  ->constrained('addresses')->onDelete('set null');
            $table->unsignedInteger('total_cents'); // represent price in whole cents
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
