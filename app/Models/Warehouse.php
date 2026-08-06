<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'warehouse_code',
        'warehouse_name',
        'location',
    ];

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'warehouse_code', 'warehouse_code');
    }
}
