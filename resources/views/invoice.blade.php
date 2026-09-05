<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">

    <title>Invoice {{ $invoice->invoice_number }}</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            width: 100%;
            margin-bottom: 25px;
        }

        .header table {
            width: 100%;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
        }

        .invoice-title {
            text-align: right;
            font-size: 24px;
            font-weight: bold;
        }

        .details {
            width: 100%;
            margin-bottom: 20px;
        }

        .details td {
            padding: 4px;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
        }

        .items th {
            background: #f2f2f2;
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        .items td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        .number {
            text-align: right;
        }

        .totals {
            width: 40%;
            margin-left: auto;
            margin-top: 20px;
        }

        .totals td {
            padding: 5px;
        }

        .grand-total {
            font-weight: bold;
            font-size: 14px;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
        }
    </style>
</head>

<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="company-name">
                        GILPOS
                    </div>

                    <div>
                        Sales & Point of Sale
                    </div>
                </td>

                <td class="invoice-title">
                    INVOICE
                </td>
            </tr>
        </table>
    </div>

    <table class="details">
        <tr>
            <td>
                <strong>Invoice Number:</strong>
                {{ $invoice->invoice_number }}
            </td>

            <td>
                <strong>Posting Date:</strong>
                {{ $invoice->posting_date }}
            </td>
        </tr>

        <tr>
            <td>
                <strong>Customer:</strong>
                {{ $invoice->customer->first_name ?? '' }}
                {{ $invoice->customer->last_name ?? '' }}
            </td>

            <td>
                <strong>Sales Employee:</strong>
                {{ $invoice->salesEmployee->first_name ?? '' }}
                {{ $invoice->salesEmployee->last_name ?? '' }}
            </td>
        </tr>
    </table>

    <table class="items">

        <thead>
            <tr>
                <th>#</th>
                <th>Item Code</th>
                <th>Description</th>
                <th class="number">Qty</th>
                <th class="number">Unit Price</th>
                <th class="number">Discount</th>
                <th class="number">Total</th>
            </tr>
        </thead>

        <tbody>

            @foreach($invoice->lines as $index => $line)

                <tr>
                    <td>{{ $index + 1 }}</td>

                    <td>
                        {{ $line->item->item_code }}
                    </td>

                    <td>
                        {{ $line->item->item_description }}
                    </td>

                    <td class="number">
                        {{ number_format($line->quantity, 1) }}
                    </td>

                    <td class="number">
                        KES {{ number_format($line->unit_price, 2) }}
                    </td>

                    <td class="number">
                        {{ number_format($line->discount_percent, 2) }}%
                    </td>

                    <td class="number">
                        KES {{ number_format($line->line_total, 2) }}
                    </td>
                </tr>

            @endforeach

        </tbody>

    </table>

    <table class="totals">

        <tr>
            <td>Total Before Discount:</td>

            <td class="number">
                KES {{ number_format($invoice->total_before_discount, 2) }}
            </td>
        </tr>

        <tr>
            <td>Total Discount:</td>

            <td class="number">
                KES {{ number_format($invoice->total_discount, 2) }}
            </td>
        </tr>

        <tr class="grand-total">
            <td>Total Amount:</td>

            <td class="number">
                KES {{ number_format($invoice->total_after_discount, 2) }}
            </td>
        </tr>

    </table>

    @if($invoice->remarks)

        <div style="margin-top: 30px;">
            <strong>Remarks:</strong>

            <p>
                {{ $invoice->remarks }}
            </p>
        </div>

    @endif

    <div class="footer">
        Thank you for your business.
    </div>

</body>
</html>