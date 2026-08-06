<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('item_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained()
                ->restrictOnDelete();

            /*
             |--------------------------------------------------
             | Snapshot fields
             |--------------------------------------------------
             | These preserve exactly what was sold even if the
             | item master changes later.
             */

            $table->string('item_code');

            $table->string('item_description');

            $table->string('unit_of_measure');

            $table->string('vat_code');

            /*
             |--------------------------------------------------
             | Transaction values
             |--------------------------------------------------
             */

            $table->decimal('quantity', 10, 2);

            $table->decimal('unit_price', 14, 3);

            $table->decimal('price_before_discount', 14, 3);

            $table->decimal('discount_percent', 5, 2)->default(0);

            $table->decimal('price_after_discount', 14, 3);

            $table->decimal('vat_percent', 5, 2)->default(16);

            $table->decimal('vat_amount', 14, 3)->default(0);

            $table->decimal('line_total', 14, 3);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};