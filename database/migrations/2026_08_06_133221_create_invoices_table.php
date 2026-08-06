<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->string('invoice_number')->unique();

            $table->foreignId('customer_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('sales_employee_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->date('posting_date');

            $table->text('remarks')->nullable();

            $table->enum('status', [
                'Draft',
                'Completed',
            ])->default('Completed');

            // Footer totals
            $table->decimal('total_before_discount', 14, 3)->default(0);
            $table->decimal('total_discount', 14, 3)->default(0);
            $table->decimal('total_after_discount', 14, 3)->default(0);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};