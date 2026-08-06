<?php

namespace App\Filament\Resources\Items\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('item_code')
                    ->required(),
                TextInput::make('item_description')
                    ->required(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('KES'),
                TextInput::make('unit_of_measure')
                    ->required(),
                TextInput::make('vat_code')
                    ->required(),
                TextInput::make('vat_percent')
                    ->required()
                    ->numeric()
                    ->suffix('%'),

            ]);
    }
}
