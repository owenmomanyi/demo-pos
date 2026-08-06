<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'sales_employee_id',
        'posting_date',
        'remarks',
        'status',
        'total_before_discount',
        'total_discount',
        'total_after_discount',
    ];

    protected $casts = [
        'posting_date' => 'date',
        'total_before_discount' => 'decimal:3',
        'total_discount' => 'decimal:3',
        'total_after_discount' => 'decimal:3',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesEmployee(): BelongsTo
    {
        return $this->belongsTo(SalesEmployee::class);
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    protected static function booted(): void
    {
        static::creating(function ($invoice) {

            $lastInvoice = self::withTrashed()
                ->latest('id')
                ->first();

            $nextNumber = $lastInvoice
                ? ((int) substr($lastInvoice->invoice_number, 3)) + 1
                : 1;

            $invoice->invoice_number = 'INV'.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }
}
