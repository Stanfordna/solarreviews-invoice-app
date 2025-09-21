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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            // Foreign key would add unnecessary complexity
            $table->string('street');
            $table->string('city');
            $table->string('postal_code');
            $table->string('country');
            $table->timestamps();
            $table->unique(['street', 'city', 'postal_code', 'country']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
