<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('warehouse_code')
                    ->required(),
                TextInput::make('warehouse_name')
                    ->required(),
                TextInput::make('location')
                    ->required(),
            ]);
    }
}
