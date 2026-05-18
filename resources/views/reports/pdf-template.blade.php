<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $payload['title'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
        .summary { margin-bottom: 12px; }
        .summary span { display: inline-block; margin-right: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ $payload['title'] }}</h1>
    <div class="summary">
        @foreach ($payload['summary'] as $card)
            <span>{{ $card['label'] }}: {{ $card['value'] }}</span>
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
