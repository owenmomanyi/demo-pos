<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('downloadPdf')
                ->label('PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn ($record) => route('invoices.pdf', $record))
                ->openUrlInNewTab(),
        ];
    }
}
