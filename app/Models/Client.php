<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;
    protected $fillable = ['full_name', 'email'];

    /**
     * Get the invoices for the client.
     */
    public function invoices(): HasMany
    {
        return $this->HasMany(Invoice::class);
    }
}
