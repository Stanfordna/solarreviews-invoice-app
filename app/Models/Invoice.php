<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory;
    protected $fillable = ['issue_date', 'due_date', 'description', 'payment_terms', 'client_id', 'status', 'sender_address_id', 'client_address_id', 'total_cents'];

    /**
     * Indicates if the IDs are auto-incrementing.
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     * @var string
     */
    protected $keyType = 'string'; 

    /**
     * The "type" of the primary key ID.
     * @var string
     */
    protected static function booted()
    {
        static::creating(function ($invoice) {
            $invoice->id = self::generateCustomId();
        });
    }

    /**
     * Generate a custom unique ID for the invoice.
     * TODO: this implementation will be problematic as more than 
     * half of all possible IDs are taken. Future implementation could 
     * use a static set of all possible remaining IDs or a prefix tree.
     * @return string
     */
    private static function generateCustomId()
    {
        do {
            // Generate the 2 random uppercase characters
            $chars = Str::upper(Str::random(2));
            // Generate 4 random digits
            $digits = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $id = $chars . $digits;
        } while (self::where('product_id', $id)->exists());

        return $id;
    }
}
