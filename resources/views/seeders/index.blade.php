<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Seeder Installation | {{ config('app.name', 'Jewellery Chit') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            body {
                background: #f4f6f8;
                color: #1f2937;
                min-height: 100vh;
            }

            .installer-shell {
                align-items: center;
                display: flex;
                min-height: 100vh;
                padding: 32px 16px;
            }

            .installer-panel {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
                margin: 0 auto;
                max-width: 760px;
                overflow: hidden;
                width: 100%;
            }

            .installer-header {
                background: #111827;
                color: #ffffff;
                padding: 32px;
            }

            .installer-mark {
                align-items: center;
                background: #d9a441;
                border-radius: 8px;
                color: #111827;
                display: inline-flex;
                height: 44px;
                justify-content: center;
                margin-bottom: 18px;
                width: 44px;
            }

            .installer-body {
                padding: 28px 32px 32px;
            }

            .installer-row {
                align-items: center;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                display: flex;
                gap: 16px;
                justify-content: space-between;
                padding: 14px 16px;
            }

            .installer-row + .installer-row {
                margin-top: 10px;
            }

            .installer-icon {
                align-items: center;
                background: #f3f4f6;
                border-radius: 8px;
                color: #374151;
                display: inline-flex;
                flex: 0 0 auto;
                height: 38px;
                justify-content: center;
                width: 38px;
            }

            @media (max-width: 575.98px) {
                .installer-header,
                .installer-body {
                    padding: 24px 20px;
                }

                .installer-row {
                    align-items: flex-start;
                    flex-direction: column;
                }
            }
        </style>
    </head>
    <body>
        <main class="installer-shell">
            <section class="installer-panel">
                <div class="installer-header">
                    <span class="installer-mark">
                        <i class="bi bi-database-check fs-4"></i>
                    </span>
                    <h1 class="h3 mb-2">Seeder Installation</h1>
                    <p class="mb-0 text-white-50">{{ $pendingCount }} pending / {{ count($seeders) }} total</p>
                </div>

                <div class="installer-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-warning" role="alert">{{ session('error') }}</div>
                    @endif

                    <div class="mb-4">
                        @foreach ($seeders as $seeder)
                            <div class="installer-row">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="installer-icon">
                                        <i class="bi {{ $seeder['icon'] }}"></i>
                                    </span>
                                    <div>
                                        <div class="fw-semibold">{{ $seeder['label'] }}</div>
                                        <div class="text-muted small">{{ $seeder['summary'] }}</div>
                                    </div>
                                </div>
                                <span @class([
                                    'badge',
                                    'bg-success-subtle text-success' => $seeder['done'],
                                    'bg-warning-subtle text-warning' => ! $seeder['done'],
                                ])>
                                    {{ $seeder['done'] ? 'Done' : 'Pending' }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <form action="{{ route('seeders.run') }}" method="POST" onsubmit="return confirm('Are you sure you want to run all pending seeders?');">
                        @csrf

                        <button type="submit" class="btn btn-primary w-100 py-2" @disabled($pendingCount === 0)>
                            <i class="bi bi-play-circle me-1"></i>
                            {{ $pendingCount === 0 ? 'Seeder already completed' : 'Run all seeders' }}
                        </button>
                    </form>
                </div>
            </section>
        </main>
    </body>
</html>
