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
        Schema::create('invoices', function (Blueprint $table) {
            $table->string('id', 6)->primary(); // two random uppercase followed by 4 random digits
            $table->date('issue_date')->default(now());
            $table->date('due_date');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('payment_terms')->default(1);
            $table->foreignId('client_id')->constrained('clients')
                  ->onDelete('restrict'); // throw an error if you try to delete a client that has/owns an invoice
            $table->string('status')->default('draft'); // draft, pending, paid
            $table->foreignId('sender_address_id')->constrained('addresses')
                  ->onDelete('restrict'); // throw an error if you try to delete an address that is in (has, owns) an invoice
            $table->foreignId('client_address_id')->constrained('addresses')
                  ->onDelete('restrict');// throw an error if you try to delete an address that is in (has, owns) an invoice
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
