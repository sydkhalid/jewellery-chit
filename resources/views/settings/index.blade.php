@extends('layouts.admin')

@section('title', 'Admin Settings')
@section('page-title', 'Admin Settings')
@section('page-eyebrow', 'Settings')

@section('content')
    @php
        $tabs = [
            'shop' => ['title' => 'Shop Details', 'route' => route('settings.shop')],
            'receipt' => ['title' => 'Receipt Settings', 'route' => route('settings.receipt')],
            'chit' => ['title' => 'Chit Settings', 'route' => route('settings.chit')],
            'message' => ['title' => 'WhatsApp/SMS Settings', 'route' => route('settings.message')],
        ];

        if (auth()->user()?->can('settings.backup')) {
            $tabs['backup'] = ['title' => 'Backup Settings', 'route' => route('settings.backup')];
        }
    @endphp

    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">System Configuration</h2>
            <p class="admin-section-copy">Manage shop details, numbering prefixes, chit defaults, messaging provider keys, and backup settings.</p>
        </div>
    </div>

    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" data-ajax-form="settings">
        @csrf
        <input type="hidden" name="active_tab" value="{{ $activeTab }}">

        <div class="admin-card">
            <ul class="nav nav-tabs settings-tabs mb-4" role="tablist">
                @foreach ($tabs as $key => $tab)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $activeTab === $key ? 'active' : '' }}" href="{{ $tab['route'] }}">
                            {{ $tab['title'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade {{ $activeTab === 'shop' ? 'show active' : '' }}">
                    @include('settings.partials.shop')
                </div>
                <div class="tab-pane fade {{ $activeTab === 'receipt' ? 'show active' : '' }}">
                    @include('settings.partials.receipt')
                </div>
                <div class="tab-pane fade {{ $activeTab === 'chit' ? 'show active' : '' }}">
                    @include('settings.partials.chit')
                </div>
                <div class="tab-pane fade {{ $activeTab === 'message' ? 'show active' : '' }}">
                    @include('settings.partials.message')
                </div>
                @can('settings.backup')
                    <div class="tab-pane fade {{ $activeTab === 'backup' ? 'show active' : '' }}">
                        @include('settings.partials.backup')
                    </div>
                @endcan
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="{{ route('settings.index') }}" class="btn btn-light">Reset View</a>
                @can('settings.edit')
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i>Save Settings
                    </button>
                @endcan
            </div>
        </div>
    </form>
@endsection
