<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineItem extends Model
{
    use HasFactory;
    protected $fillable = ['invoice_id', 'name', 'quantity', 'price_unit_cents', 'price_total_cents'];
}
