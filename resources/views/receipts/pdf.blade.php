<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $receipt->receipt_no }} Receipt</title>
    <style>
        @page { margin: 22mm 18mm; }
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 0; }
        .receipt-document { width: 100%; }
        .receipt-header { border-bottom: 2px solid #111827; min-height: 66px; padding-bottom: 14px; width: 100%; }
        .receipt-header:after, .receipt-title-row:after, .receipt-summary:after, .receipt-footer:after { clear: both; content: ""; display: block; }
        .receipt-logo { float: left; width: 70px; }
        .receipt-logo img { max-height: 58px; max-width: 58px; }
        .receipt-logo span { align-items: center; background: #111827; border-radius: 8px; color: #fff; display: inline-block; font-size: 20px; font-weight: 700; height: 54px; line-height: 54px; text-align: center; width: 54px; }
        .receipt-shop { float: left; width: 60%; }
        .receipt-shop h1 { font-size: 22px; margin: 0 0 4px; }
        .receipt-shop p { margin: 2px 0; }
        .receipt-copy-label { color: #b45309; float: right; font-size: 14px; font-weight: 700; text-align: right; text-transform: uppercase; width: 150px; }
        .receipt-title-row { border-bottom: 1px solid #d1d5db; margin: 16px 0; padding-bottom: 10px; width: 100%; }
        .receipt-title-row h2 { float: left; font-size: 18px; margin: 0; }
        .receipt-title-row div { float: right; text-align: right; }
        .receipt-title-row span { display: block; margin-top: 4px; }
        .receipt-grid { margin-bottom: 18px; width: 100%; }
        .receipt-grid > div { border: 1px solid #d1d5db; display: inline-block; min-height: 145px; padding: 12px; vertical-align: top; width: 45%; }
        .receipt-grid > div + div { margin-left: 2%; }
        h3 { font-size: 13px; margin: 0 0 8px; text-transform: uppercase; }
        dl { margin: 0; }
        dt { color: #6b7280; float: left; font-weight: 700; width: 105px; }
        dd { margin: 0 0 7px 112px; }
        .receipt-table { border-collapse: collapse; margin-bottom: 18px; width: 100%; }
        .receipt-table th { background: #111827; color: #fff; }
        .receipt-table th, .receipt-table td { border: 1px solid #d1d5db; padding: 8px; }
        .text-right { text-align: right; }
        .receipt-summary { width: 100%; }
        .receipt-payment-meta { float: left; width: 48%; }
        .receipt-summary table { float: right; width: 48%; }
        .receipt-summary table { border-collapse: collapse; margin-left: auto; }
        .receipt-summary th, .receipt-summary td { border-bottom: 1px solid #d1d5db; padding: 7px 8px; text-align: right; }
        .receipt-total th, .receipt-total td { color: #111827; font-size: 15px; font-weight: 700; }
        .receipt-footer { border-top: 1px solid #d1d5db; margin-top: 28px; padding-top: 14px; width: 100%; }
        .receipt-footer > div:first-child { float: left; width: 62%; }
        .signature-box { float: right; text-align: right; width: 34%; }
        .signature-box span { border-top: 1px solid #111827; display: inline-block; margin-top: 50px; padding-top: 7px; }
    </style>
</head>
<body>
    @include('receipts.partials.document')

    @if (($autoPrint ?? false) === true)
        <script>
            window.addEventListener('load', () => window.print());
        </script>
    @endif
</body>
</html>
