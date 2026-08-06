<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\SalesEmployee;
use App\Models\Warehouse;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Invoice Header')
                    ->columns(2)
                    ->schema([

                        TextInput::make('invoice_number')
                            ->label('Invoice No.')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(
                                fn (?string $state) => $state ?? 'Auto Generated'
                            ),

                        DatePicker::make('posting_date')
                            ->label('Posting Date')
                            ->default(now())
                            ->native(false)
                            ->required(),

                        Hidden::make('customer_id'),

                        Select::make('customer_code')
                            ->label('Customer Code')
                            ->options(fn () => Customer::query()
                                ->orderBy('customer_code', 'asc')
                                ->pluck('customer_code', 'id'))
                            ->searchable()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! $state) {
                                    $set('customer_id', null);
                                    $set('customer_name', null);

                                    return;
                                }

                                $set('customer_id', $state);
                                $set('customer_name', $state);
                            })
                            ->required(),

                        Select::make('customer_name')
                            ->label('Customer Name')
                            ->options(fn () => Customer::query()
                                ->orderBy('first_name', 'asc')
                                ->get()
                                ->mapWithKeys(fn (Customer $customer) => [
                                    $customer->id => $customer->full_name,
                                ]))
                            ->searchable()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! $state) {
                                    $set('customer_id', null);
                                    $set('customer_code', null);

                                    return;
                                }

                                $set('customer_id', $state);
                                $set('customer_code', $state);
                            })
                            ->required(),

                    ]),
                Section::make('Invoice Lines')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('invoiceLines')
                            ->relationship()
                            ->live()
                            ->defaultItems(1)
                            ->collapsible(false)
                            ->cloneable(false)
                            ->reorderable(false)
                            ->addActionLabel('Add Item')
                            ->afterStateUpdated(function (?array $state, Set $set) {
                                self::updateInvoiceTotals($state ?? [], $set);
                            })
                            ->columns(12)
                            ->schema([
                                Select::make('item_id')
                                    ->label('Item No.')
                                    ->relationship(
                                        name: 'item',
                                        titleAttribute: 'item_code',
                                    )
                                    ->searchable(['item_code', 'item_description'])
                                    ->preload()
                                    ->live()
                                    ->columnSpan(2)
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $item = Item::find($state, ['*']);

                                        if (! $item) {
                                            return;
                                        }

                                        $set('item_code', $item->item_code);
                                        $set('item_description', $item->item_description);
                                        $set('unit_of_measure', $item->unit_of_measure);
                                        $set('vat_code', $item->vat_code);
                                        $set('vat_percent', (float) ($item->vat_percent ?? 0));
                                        $set('unit_price', $item->price);
                                        $set('price_before_discount', $item->price);
                                        $set('discount_percent', 0);
                                        $set('quantity', 1);
                                        $set('price_after_discount', $item->price);
                                        $set('vat_amount', 0);
                                        $set('line_total', $item->price);
                                    })
                                    ->required(),

                                Hidden::make('item_code')
                                    ->dehydrated(),

                                Hidden::make('unit_of_measure')
                                    ->dehydrated(),

                                Hidden::make('vat_code')
                                    ->dehydrated(),

                                Hidden::make('vat_percent')
                                    ->default(0)
                                    ->dehydrated(),

                                Hidden::make('vat_amount')
                                    ->default(0)
                                    ->dehydrated(),

                                Hidden::make('line_total')
                                    ->default(0)
                                    ->dehydrated(),

                                TextInput::make('item_description')
                                    ->label('Description')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(3),

                                Select::make('warehouse_id')
                                    ->label('Warehouse')
                                    ->relationship(
                                        name: 'warehouse',
                                        titleAttribute: 'warehouse_name',
                                    )
                                    ->searchable(['warehouse_code', 'warehouse_name'])
                                    ->preload()
                                    ->live()
                                    ->columnSpan(3)
                                    ->required()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $itemCode = Item::query()
                                            ->whereKey($get('item_id'))
                                            ->value('item_code');

                                        $warehouseCode = Warehouse::query()
                                            ->whereKey($state)
                                            ->value('warehouse_code');

                                        $inventory = Inventory::query()
                                            ->where('item_code', $itemCode)
                                            ->where('warehouse_code', $warehouseCode)
                                            ->first();

                                        $set(
                                            'quantity_available',
                                            $inventory?->quantity_on_hand ?? 0
                                        );
                                    }),

                                TextInput::make('quantity_available')
                                    ->label('Qty in WHSE')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->columnSpan(1),

                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateDiscountedPrice($get, $set);
                                    })
                                    ->columnSpan(1),                                

                                TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->prefix('KES')
                                    ->columnSpan(2),

                                TextInput::make('price_before_discount')
                                    ->label('Price Before Disc')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state): string => self::formatMoney($state))
                                    ->prefix('KES')
                                    ->columnSpan(2),

                                TextInput::make('discount_percent')
                                    ->label('Disc %')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('%')
                                    ->maxValue(50)
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        self::updateDiscountedPrice($get, $set);
                                    })
                                    ->columnSpan(2),

                                TextInput::make('price_after_discount')
                                    ->label('Price After Disc')
                                    ->disabled()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state): string => self::formatMoney($state))
                                    ->prefix('KES')
                                    ->columnSpan(2),
                            ]),
                    ]),
                Section::make('Invoice Footer')
                    ->columns(2)
                    ->schema([
                        Select::make('sales_employee_id')
                            ->label('Sales Employee')
                            ->relationship(
                                name: 'salesEmployee',
                                titleAttribute: 'employee_code',
                            )
                            ->searchable(['employee_code', 'first_name', 'last_name'])
                            ->getOptionLabelFromRecordUsing(fn (SalesEmployee $record): string => "{$record->employee_code} - {$record->full_name}")
                            ->preload()
                            ->required(),

                        TextInput::make('remarks')
                            ->label('Remarks')
                            ->columnSpan(2),
                    ]),

                Section::make('Totals')
                    ->columns(3)
                    ->schema([
                        TextInput::make('total_before_discount')
                            ->label('Total Before Discount')
                            ->disabled()
                            ->dehydrated()
                            ->formatStateUsing(fn ($state): string => self::formatMoney($state))
                            ->prefix('KES'),

                        TextInput::make('total_discount')
                            ->label('Total Discount')
                            ->disabled()
                            ->dehydrated()
                            ->formatStateUsing(fn ($state): string => self::formatMoney($state))
                            ->prefix('KES'),

                        TextInput::make('total_after_discount')
                            ->label('Price After Discount')
                            ->disabled()
                            ->dehydrated()
                            ->formatStateUsing(fn ($state): string => self::formatMoney($state))
                            ->prefix('KES'),
                    ]),

            ]);
    }

    protected static function updateDiscountedPrice(Get $get, Set $set): void
    {
        $quantity = (float) ($get('quantity') ?: 0);
        $unitPrice = (float) ($get('unit_price') ?: 0);
        $discountPercent = (float) ($get('discount_percent') ?: 0);
        $vatPercent = (float) ($get('vat_percent') ?: 0);

        $priceBeforeDiscount = round($quantity * $unitPrice, 3);
        $priceAfterDiscount = round($priceBeforeDiscount * (1 - (max($discountPercent, 0) / 100)), 3);
        $vatAmount = round($priceAfterDiscount * (max($vatPercent, 0) / 100), 3);
        $lineTotal = round($priceAfterDiscount + $vatAmount, 3);

        $set('price_before_discount', $priceBeforeDiscount);
        $set('price_after_discount', $priceAfterDiscount);
        $set('vat_amount', $vatAmount);
        $set('line_total', $lineTotal);

        self::updateNestedInvoiceTotals($get, $set);
    }

    protected static function updateInvoiceTotals(array $lines, Set $set): void
    {
        $totalBeforeDiscount = round(collect($lines)->sum(function (array $line): float {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);

            return $quantity * $unitPrice;
        }), 3);

        $totalAfterDiscount = round(collect($lines)->sum(function (array $line): float {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $discountPercent = (float) ($line['discount_percent'] ?? 0);
            $before = $quantity * $unitPrice;

            return $before * (1 - (max($discountPercent, 0) / 100));
        }), 3);

        $set('total_before_discount', $totalBeforeDiscount);
        $set('total_discount', round($totalBeforeDiscount - $totalAfterDiscount, 3));
        $set('total_after_discount', $totalAfterDiscount);
    }

    protected static function updateNestedInvoiceTotals(Get $get, Set $set): void
    {
        $lines = $get('../../invoiceLines');

        if (! is_array($lines)) {
            return;
        }

        $totalBeforeDiscount = round(collect($lines)->sum(function (array $line): float {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);

            return $quantity * $unitPrice;
        }), 3);

        $totalAfterDiscount = round(collect($lines)->sum(function (array $line): float {
            $quantity = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $discountPercent = (float) ($line['discount_percent'] ?? 0);
            $before = $quantity * $unitPrice;

            return $before * (1 - (max($discountPercent, 0) / 100));
        }), 3);

        $set('../../total_before_discount', $totalBeforeDiscount);
        $set('../../total_discount', round($totalBeforeDiscount - $totalAfterDiscount, 3));
        $set('../../total_after_discount', $totalAfterDiscount);
    }

    protected static function formatMoney(mixed $state): string
    {
        return number_format((float) ($state ?? 0), 3, '.', '');
    }
}
