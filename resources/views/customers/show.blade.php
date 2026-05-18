@extends('layouts.admin')

@section('title', $customer->customer_code)
@section('page-title', 'Customer Profile')
@section('page-eyebrow', 'Customer Management')

@section('content')
    <div class="admin-page-actions">
        <div>
            <h2 class="admin-section-title">{{ $customer->name }}</h2>
            <p class="admin-section-copy">{{ $customer->customer_code }} · {{ $customer->mobile }}</p>
        </div>

        <div class="d-flex flex-wrap gap-2">
            @can('customers.edit')
                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
            @endcan
            <a href="{{ route('customers.index') }}" class="btn btn-light">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>

    <div class="customer-profile-grid">
        <div class="admin-card customer-summary-card">
            <div class="customer-photo">
                @if ($customer->photo)
                    <img src="{{ asset('storage/'.$customer->photo) }}" alt="{{ $customer->name }}">
                @else
                    <span>{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                @endif
            </div>
            <h3>{{ $customer->name }}</h3>
            <p>{{ $customer->customer_code }}</p>
            <span class="badge rounded-pill text-bg-{{ $customer->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($customer->status) }}</span>

            <div class="customer-summary-list">
                <div>
                    <span>Mobile</span>
                    <strong>{{ $customer->mobile }}</strong>
                </div>
                <div>
                    <span>Email</span>
                    <strong>{{ $customer->email ?: 'Not added' }}</strong>
                </div>
                <div>
                    <span>Address</span>
                    <strong>{{ $customer->full_address ?: $customer->address }}</strong>
                </div>
            </div>
        </div>

        <div class="admin-card">
            <ul class="nav nav-pills customer-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'profile' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#profile-tab" type="button" role="tab">Profile</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'documents' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#documents-tab" type="button" role="tab">Documents</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'ledger' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#ledger-tab" type="button" role="tab">Ledger</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'payments' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#payments-tab" type="button" role="tab">Payments</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $activeTab === 'outstanding' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#outstanding-tab" type="button" role="tab">Outstanding</button>
                </li>
            </ul>

            <div class="tab-content pt-3">
                <div class="tab-pane fade {{ $activeTab === 'profile' ? 'show active' : '' }}" id="profile-tab" role="tabpanel">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="detail-panel">
                                <h4>Customer Details</h4>
                                <dl>
                                    <dt>Aadhaar</dt>
                                    <dd>{{ $customer->aadhaar_no ?: 'Not added' }}</dd>
                                    <dt>PAN</dt>
                                    <dd>{{ $customer->pan_no ?: 'Not added' }}</dd>
                                    <dt>Alternate Mobile</dt>
                                    <dd>{{ $customer->alternate_mobile ?: 'Not added' }}</dd>
                                </dl>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-panel">
                                <h4>Nominee Details</h4>
                                <dl>
                                    <dt>Name</dt>
                                    <dd>{{ $customer->nominee?->name ?: 'Not added' }}</dd>
                                    <dt>Relationship</dt>
                                    <dd>{{ $customer->nominee?->relationship ?: 'Not added' }}</dd>
                                    <dt>Mobile</dt>
                                    <dd>{{ $customer->nominee?->mobile ?: 'Not added' }}</dd>
                                    <dt>Address</dt>
                                    <dd>{{ $customer->nominee?->address ?: 'Not added' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="detail-panel mt-3">
                        <h4>Active Chit Accounts</h4>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Chit No</th>
                                        <th>Scheme</th>
                                        <th>Status</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end">Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($customer->enrollments as $enrollment)
                                        <tr>
                                            <td>{{ $enrollment->chit_no }}</td>
                                            <td>{{ $enrollment->scheme?->name ?: 'Scheme' }}</td>
                                            <td><span class="badge text-bg-light">{{ ucfirst($enrollment->status) }}</span></td>
                                            <td class="text-end">Rs. {{ number_format((float) $enrollment->total_paid, 2) }}</td>
                                            <td class="text-end">Rs. {{ number_format((float) $enrollment->balance_amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No chit accounts found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade {{ $activeTab === 'documents' ? 'show active' : '' }}" id="documents-tab" role="tabpanel">
                    @include('customers.partials.documents')
                </div>

                <div class="tab-pane fade {{ $activeTab === 'ledger' ? 'show active' : '' }}" id="ledger-tab" role="tabpanel">
                    @include('customers.partials.ledger')
                </div>

                <div class="tab-pane fade {{ $activeTab === 'payments' ? 'show active' : '' }}" id="payments-tab" role="tabpanel">
                    @include('customers.partials.payment-history')
                </div>

                <div class="tab-pane fade {{ $activeTab === 'outstanding' ? 'show active' : '' }}" id="outstanding-tab" role="tabpanel">
                    @include('customers.partials.outstanding')
                </div>
            </div>
        </div>
    </div>
@endsection
