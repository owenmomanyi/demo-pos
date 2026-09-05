<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfController extends Controller
{
    public function download(Invoice $invoice)
    {
        $invoice->load([
            'customer',
            'salesEmployee',
            'inviceLines',
        ]);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
        ]);

        return $pdf
            ->setPaper('a4', 'portrait')
            ->download(
                'Invoice-' . $invoice->invoice_number . '.pdf'
            );
    }
}