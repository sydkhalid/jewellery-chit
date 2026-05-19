<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $receipt->receipt_no }} Thermal Receipt</title>
    <style>
        * { box-sizing: border-box; }
        body { background: #f3f4f6; color: #111827; font-family: Arial, sans-serif; font-size: 11px; margin: 0; }
        .thermal-receipt { background: #ffffff; box-shadow: 0 12px 30px rgba(17, 24, 39, 0.14); margin: 16px auto; padding: 8px; width: 76mm; }
        .center { text-align: center; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        p { margin: 2px 0; }
        .line { border-top: 1px dashed #111827; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; margin: 3px 0; }
        .total { font-size: 13px; font-weight: 700; }
        table { border-collapse: collapse; margin: 6px 0; width: 100%; }
        th, td { border-bottom: 1px dashed #9ca3af; padding: 4px 0; text-align: left; }
        th:last-child, td:last-child { text-align: right; }
        .copy { font-weight: 700; text-transform: uppercase; }
        @media print {
            @page { margin: 0; size: 80mm auto; }
            body { background: #ffffff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .thermal-receipt { box-shadow: none; margin: 0 auto; width: 76mm; }
            tr { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <section class="thermal-receipt">
        <div class="center">
            <h1>{{ $shop['name'] ?? config('app.name', 'Jewellery Chit') }}</h1>
            <p>{{ $shop['address'] ?? 'Shop address not configured' }}</p>
            @if (! blank($shop['mobile'] ?? null))
                <p>Mobile: {{ $shop['mobile'] }}</p>
            @endif
            @if (! blank($shop['gstin'] ?? null))
                <p>GSTIN: {{ $shop['gstin'] }}</p>
            @endif
            <p class="copy">{{ $copyLabel }}</p>
        </div>

        <div class="line"></div>
        <div class="row"><span>Receipt</span><strong>{{ $receipt->receipt_no }}</strong></div>
        <div class="row"><span>Date</span><strong>{{ optional($receipt->receipt_date)->format('d M Y') }}</strong></div>
        <div class="row"><span>Customer</span><strong>{{ $customer?->name }}</strong></div>
        <div class="row"><span>Mobile</span><strong>{{ $customer?->mobile }}</strong></div>
        <div class="row"><span>Chit</span><strong>{{ $enrollment?->chit_no }}</strong></div>
        <div class="row"><span>Scheme</span><strong>{{ $enrollment?->scheme?->name }}</strong></div>

        <div class="line"></div>
        <table>
            <thead>
                <tr>
                    <th>Inst.</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($allocations as $allocation)
                    <tr>
                        <td>#{{ $allocation->installment?->installment_no }}</td>
                        <td>Rs. {{ number_format((float) $allocation->amount + (float) $allocation->late_fee_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="row"><span>Mode</span><strong>{{ $payment?->paymentMode?->name ?: '-' }}</strong></div>
        <div class="row"><span>Payment No</span><strong>{{ $payment?->payment_no }}</strong></div>
        <div class="row"><span>Late Fee</span><strong>Rs. {{ number_format((float) $payment?->late_fee_amount, 2) }}</strong></div>
        <div class="row total"><span>Total</span><strong>Rs. {{ number_format((float) $receipt->amount, 2) }}</strong></div>

        <div class="line"></div>
        <p>Collected by: {{ $payment?->staff?->name ?: '-' }}</p>
        <p>{{ $shop['terms'] ?? 'Please keep this receipt for future reference.' }}</p>
        <p class="center">Authorized Signature</p>
    </section>

    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
