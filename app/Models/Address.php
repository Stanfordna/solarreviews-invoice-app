<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Address extends Model
{
    use HasFactory;
    protected $fillable = ['street', 'city', 'postal_code', 'country'];

    /**
     * We must define the relationship between sender/client addresses and Invoices separately
     * I think this is the most understandable way to get all the invoices that contain an address
     */
    public function senderInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'sender_address_id');
    }

    public function clientInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'client_address_id');
    }

    /**
     * Merge the two lists and return the resulting list.
     * 
     * Importantly, this is no longer an Eloquent Relation object,
     * but a Collection, so you can't use the ORM to chain database
     * queries off the returned list. If that is needed use 
     * senderInvoices() and clientInvoices() individually, as 
     * in the deleting event of Invoices.php.
     * Certain libraries can create a HasMany relationship off a
     * composite foreign key ['sender_address_id', 'client_address_id']
     * but it's overkill here to install another library
     */
    public function invoices(): Collection
    {
        return $this->senderInvoices->merge($this->clientInvoices);
    }
}
