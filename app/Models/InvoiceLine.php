<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceLine extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_id',
        'item_id',
        'warehouse_id',
        'item_code',
        'item_description',
        'unit_of_measure',
        'vat_code',
        'quantity',
        'unit_price',
        'discount_percent',
        'price_before_discount',
        'price_after_discount',
        'vat_percent',
        'vat_amount',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:3',
        'discount_percent' => 'decimal:2',
        'price_before_discount' => 'decimal:3',
        'price_after_discount' => 'decimal:3',
        'line_total' => 'decimal:3',
    ];


    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse(): BelongsTo
{
    return $this->belongsTo(Warehouse::class);
}
}
