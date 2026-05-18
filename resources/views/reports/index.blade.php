@extends('layouts.admin')

@section('title', 'Reports')
@section('page-title', 'Reports')
@section('page-eyebrow', 'Reporting')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">Reports</h2>
            <p class="admin-section-copy">Open operational reports with filters, print, Excel, and PDF exports.</p>
        </div>
    </div>

    <div class="row g-4">
        @foreach ($definitions as $type => $definition)
            <div class="col-md-6 col-xl-4">
                <a class="admin-card h-100 text-decoration-none d-block" href="{{ route($definition['route']) }}">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <div>
                            <h3 class="form-section-title mb-2">{{ $definition['title'] }}</h3>
                            <div class="text-muted small">{{ count($definition['columns']) }} columns</div>
                        </div>
                        <i class="bi bi-arrow-right text-muted"></i>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endsection
