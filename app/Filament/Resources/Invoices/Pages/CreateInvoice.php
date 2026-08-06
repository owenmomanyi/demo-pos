<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\Warehouse;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected const APPROVAL_THRESHOLD = 10000;

    protected array $inventoryReservations = [];

    protected function beforeCreate(): void
    {
        $lines = $this->data['invoiceLines'] ?? [];

        if ($lines === []) {
            return;
        }

        $itemCodes = Item::query()
            ->whereKey(collect($lines)->pluck('item_id')->filter()->all())
            ->pluck('item_code', 'id');

        $warehouseCodes = Warehouse::query()
            ->whereKey(collect($lines)->pluck('warehouse_id')->filter()->all())
            ->pluck('warehouse_code', 'id');

        $demandByInventory = [];

        foreach ($lines as $index => $line) {
            $itemId = $line['item_id'] ?? null;
            $warehouseId = $line['warehouse_id'] ?? null;
            $quantity = (float) ($line['quantity'] ?? 0);

            if ((! $itemId) || (! $warehouseId) || ($quantity <= 0)) {
                continue;
            }

            $itemCode = $itemCodes->get($itemId);
            $warehouseCode = $warehouseCodes->get($warehouseId);

            if ((! $itemCode) || (! $warehouseCode)) {
                continue;
            }

            $inventoryKey = $this->getInventoryKey($itemCode, $warehouseCode);

            if (! array_key_exists($inventoryKey, $demandByInventory)) {
                $demandByInventory[$inventoryKey] = [
                    'item_code' => $itemCode,
                    'warehouse_code' => $warehouseCode,
                    'quantity' => 0,
                    'line_index' => $index,
                ];
            }

            $demandByInventory[$inventoryKey]['quantity'] += $quantity;
        }

        if ($demandByInventory === []) {
            return;
        }

        $inventories = Inventory::query()
            ->where(function ($query) use ($demandByInventory) {
                foreach ($demandByInventory as $demand) {
                    $query->orWhere(function ($nestedQuery) use ($demand) {
                        $nestedQuery
                            ->where('item_code', $demand['item_code'])
                            ->where('warehouse_code', $demand['warehouse_code']);
                    });
                }
            })
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (Inventory $inventory): string => $this->getInventoryKey($inventory->item_code, $inventory->warehouse_code));

        foreach ($demandByInventory as $inventoryKey => $demand) {
            $inventory = $inventories->get($inventoryKey);
            $availableQuantity = (float) ($inventory?->quantity_on_hand ?? 0);

            if ($availableQuantity < $demand['quantity']) {
                throw ValidationException::withMessages([
                    "data.invoiceLines.{$demand['line_index']}.quantity" => "Insufficient stock for item {$demand['item_code']} in warehouse {$demand['warehouse_code']}. Available: {$availableQuantity}, requested: {$demand['quantity']}",
                ]);
            }

            $this->inventoryReservations[$inventoryKey] = [
                'inventory_id' => $inventory->id,
                'quantity' => $demand['quantity'],
            ];
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = $this->requiresApproval($data) ? 'Draft' : 'Completed';

        return $data;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->requiresConfirmation()
            ->modalHidden(fn (): bool => ! $this->requiresApproval($this->form->getState()))
            ->modalHeading('Approval Required')
            ->modalDescription('This invoice is above 10,000 and must go for approval. Confirm to save it as Draft.')
            ->modalSubmitActionLabel('Save as Draft');
    }

    protected function afterCreate(): void
    {
        foreach ($this->inventoryReservations as $reservation) {
            Inventory::query()
                ->whereKey($reservation['inventory_id'])
                ->decrement('quantity_on_hand', $reservation['quantity']);
        }
    }

    protected function getInventoryKey(string $itemCode, string $warehouseCode): string
    {
        return $itemCode.'|'.$warehouseCode;
    }

    protected function requiresApproval(array $data): bool
    {
        $totalAfterDiscount = $this->parseAmount($data['total_after_discount'] ?? null);

        $lines = $this->extractInvoiceLines($data['invoiceLines'] ?? []);

        if ($totalAfterDiscount <= 0 && $lines !== []) {
            $totalAfterDiscount = collect($lines)->sum(
                fn (array $line): float => $this->calculateLineAfterDiscount($line)
            );
        }

        return $totalAfterDiscount > self::APPROVAL_THRESHOLD;
    }

    protected function calculateLineAfterDiscount(array $line): float
    {
        $quantity = $this->parseAmount($line['quantity'] ?? null);
        $unitPrice = $this->parseAmount($line['unit_price'] ?? null);
        $discountPercent = $this->parseAmount($line['discount_percent'] ?? null);
        $lineBeforeDiscount = $quantity * $unitPrice;

        return $lineBeforeDiscount * (1 - (max($discountPercent, 0) / 100));
    }

    protected function extractInvoiceLines(array $payload): array
    {
        $lines = [];

        foreach ($payload as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if (array_key_exists('item_id', $entry)) {
                $lines[] = $entry;

                continue;
            }

            foreach ($entry as $nested) {
                if (is_array($nested) && array_key_exists('item_id', $nested)) {
                    $lines[] = $nested;
                }
            }
        }

        return $lines;
    }

    protected function parseAmount(mixed $value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '', $value);
        }

        return (float) ($value ?? 0);
    }
}
