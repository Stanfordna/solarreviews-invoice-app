<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Address extends Model
{
    use HasFactory;
    protected $fillable = ['street', 'city', 'postal_code', 'country'];

    /**
     * We must define the relationship between sender/client addresses and Invoices separately
     * I think this is the most understandable way to get all the invoices that contain an address
     */
    public function senderInvoices() : HasMany
    {
        return $this->hasMany(Invoice::class, 'hometeam_id');
    }

    public function clientInvoices() : HasMany
    {
        return $this->hasMany(Invoice::class, 'guestteam_id');
    }

    /**
     * Merge the two lists and return the resulting list
     */
    public function invoices() : HasMany
    {
        return $this->senderInvoices->merge($this->clientInvoices);
    }
}
