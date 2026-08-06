<?php

namespace App\Filament\Resources\Inventories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InventoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('item_code')
                    ->relationship('item', 'item_code')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('warehouse_code')
                    ->relationship('warehouse', 'warehouse_code')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('quantity_on_hand')
                    ->required()
                    ->numeric()
                    ->default(0.0),
            ]);
    }
}
