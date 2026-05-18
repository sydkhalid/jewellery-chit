<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $payload['title'] }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 24px; }
        h1 { margin: 0 0 16px; }
        .summary { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 16px; }
        .summary div { border: 1px solid #e5e7eb; padding: 10px 12px; min-width: 140px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 7px; text-align: left; }
        th { background: #f3f4f6; }
        @media print { button { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <button type="button" onclick="window.print()">Print</button>
    <h1>{{ $payload['title'] }}</h1>
    <div class="summary">
        @foreach ($payload['summary'] as $card)
            <div>
                <small>{{ $card['label'] }}</small><br>
                <strong>{{ $card['value'] }}</strong>
            </div>
        @endforeach
    </div>
    <table>
        <thead>
            <tr>
                @foreach ($payload['headings'] as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($payload['rows'] as $row)
                <tr>
                    @foreach ($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($payload['headings']) }}">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
