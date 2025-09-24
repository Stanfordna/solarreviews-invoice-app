<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

        /**
         * When an invoice is deleted, all clients and addresses that do not have an invoice should be deleted
         */
        static::deleting(function ($invoice) {
            $client = $invoice->client();
            // Check if the client has other invoices
            $clientHasOtherInvoices = $client->invoices()
                ->where('id', '!=', $invoice->id)
                ->exists();

            // TODO: May need to set client_id to null before deletion in object or in db, or change behavior of client model onDelete
            if (!$clientHasOtherInvoices) {
                $client->delete();
            }

            $invoice->clientAddress()->merge($invoice->senderAddress())
                ->each(function ($address) use ($invoice) {
                    $addressInOtherInvoices = $address->invoices()
                        ->where('id', '!=', $invoice->id)
                        ->exists();

                    // TODO: May need to set sender/client_address_ids to null before deletion in object or in db, or change behavior of client model onDelete
                    if (!$addressInOtherInvoices) {
                        $address->delete();
                    }
                }
            );
        });
    }

    /**
     * Generate a custom unique ID for the invoice.
     * TODO: this implementation will be problematic as more than
     * half of all possible IDs are taken. Future implementation could
     * use a static set of all possible remaining IDs or a prefix tree.
     * @return string
     */
    private static function generateCustomId() : string
    {
        do {
            // Generate the 2 random uppercase characters
            $chars = Str::upper(Str::random(2));
            // Generate 4 random digits
            $digits = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $id = $chars . $digits;
        } while (self::where('id', $id)->exists());

        return $id;
    }

    /**
     * Get the client that owns the invoice.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the client address for the invoice.
     */
    public function clientAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'client_address_id');
    }

    /**
     * Get the sender address for the invoice.
     */
    public function senderAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'sender_address_id');
    }

    /**
     * Get the line items for the invoice.
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(LineItem::class, 'invoice_id');
    }
}
