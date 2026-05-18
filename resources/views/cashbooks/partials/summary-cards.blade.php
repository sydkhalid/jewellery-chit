<div class="row g-4 mb-4" id="cashbook-summary-cards">
    <div class="col-md-3">
        <div class="admin-card h-100">
            <div class="text-muted small">Opening Balance</div>
            <div class="metric-value" data-cashbook-summary="opening_balance">Rs. {{ number_format((float) $summary['opening_balance'], 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card h-100">
            <div class="text-muted small">Credit Total</div>
            <div class="metric-value" data-cashbook-summary="credit_total">Rs. {{ number_format((float) $summary['credit_total'], 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card h-100">
            <div class="text-muted small">Debit Total</div>
            <div class="metric-value" data-cashbook-summary="debit_total">Rs. {{ number_format((float) $summary['debit_total'], 2) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-card h-100">
            <div class="text-muted small">Closing Balance</div>
            <div class="metric-value" data-cashbook-summary="closing_balance">Rs. {{ number_format((float) $summary['closing_balance'], 2) }}</div>
        </div>
    </div>
    <div class="col-12">
        <div class="admin-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="form-section-title mb-0">Payment Mode Summary</h3>
            </div>
            <div class="row g-3" data-payment-mode-summary>
                @forelse ($paymentModeSummary as $mode)
                    <div class="col-md-3">
                        <div class="scheme-info-panel h-100">
                            <div class="text-muted small">{{ $mode['payment_mode'] }}</div>
                            <div class="fw-semibold">Rs. {{ number_format((float) $mode['credit_total'], 2) }}</div>
                            <div class="small text-muted">Net Rs. {{ number_format((float) $mode['net_total'], 2) }}</div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-muted">No payment mode entries for this period.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
