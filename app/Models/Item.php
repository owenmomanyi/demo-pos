<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'item_code',
        'item_description',
        'price',
        'unit_of_measure',
        'vat_code',
        'vat_percent',
    ];
    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'item_code', 'item_code');
    }
}
