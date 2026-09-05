<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('invoice_number', 'desc')
            ->columns([
                TextColumn::make('invoice_number')
                    ->searchable(),
                TextColumn::make('customer.customer_code')
                    ->searchable(),
                TextColumn::make('salesEmployee.employee_code')
                    ->searchable(),
                TextColumn::make('posting_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Completed' => 'success',
                        'Draft' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('total_before_discount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_discount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_after_discount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-m-check-circle')
                    ->visible(fn (Invoice $record): bool => $record->status === 'Draft')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Invoice')
                    ->modalDescription('Are you sure you want to approve this invoice? This will change the status to Completed.')
                    ->modalSubmitActionLabel('Approve')
                    ->action(function (Invoice $record): void {
                        $record->update([
                            'status' => 'Completed',
                        ]);
                    }),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
