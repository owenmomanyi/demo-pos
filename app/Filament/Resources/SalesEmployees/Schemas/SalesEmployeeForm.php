<?php

namespace App\Filament\Resources\SalesEmployees\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SalesEmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('employee_code')
                    ->required(),
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
            ]);
    }
}
