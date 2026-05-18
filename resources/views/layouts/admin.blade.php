<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Dashboard') | {{ config('app.name', 'Jewellery Chit') }}</title>

        @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/app.js', 'resources/js/admin.js'])
        @stack('styles')
    </head>
    <body class="admin-shell">
        <div class="admin-wrapper">
            @include('partials.sidebar')

            <div class="admin-main">
                @include('partials.header')

                <main class="admin-content">
                    <nav class="admin-breadcrumbs" aria-label="Breadcrumb">
                        <span>@yield('page-eyebrow', 'Jewellery Chit')</span>
                        <span>/</span>
                        <span>@yield('page-title', 'Dashboard')</span>
                    </nav>

                    @yield('content')
                </main>

                @include('partials.footer')
            </div>
        </div>

        <div class="admin-sidebar-backdrop" data-sidebar-dismiss></div>

        @include('partials.alerts')
        @include('partials.scripts')
        @stack('scripts')
    </body>
</html>
