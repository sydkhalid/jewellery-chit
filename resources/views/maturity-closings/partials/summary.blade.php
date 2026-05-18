<div class="dashboard-card-grid">
    <div class="metric-card metric-card-primary">
        <div class="metric-icon"><i class="bi bi-cash-stack"></i></div>
        <div>
            <div class="metric-label">Total Paid</div>
            <div class="metric-value">Rs. {{ number_format((float) $summary['total_paid'], 2) }}</div>
            <div class="metric-trend">{{ $summary['paid_months'] }} paid / {{ $summary['pending_months'] }} pending months</div>
        </div>
    </div>
    <div class="metric-card metric-card-success">
        <div class="metric-icon"><i class="bi bi-gift"></i></div>
        <div>
            <div class="metric-label">Shop Bonus</div>
            <div class="metric-value">Rs. {{ number_format((float) $summary['shop_bonus'], 2) }}</div>
            <div class="metric-trend">{{ ucfirst($summary['scheme']?->shop_bonus_type ?? 'none') }}</div>
        </div>
    </div>
    <div class="metric-card metric-card-warning">
        <div class="metric-icon"><i class="bi bi-dash-circle"></i></div>
        <div>
            <div class="metric-label">Deductions</div>
            <div class="metric-value">Rs. {{ number_format((float) $summary['deductions'], 2) }}</div>
            <div class="metric-trend">Late fee and closing deductions</div>
        </div>
    </div>
    <div class="metric-card metric-card-info">
        <div class="metric-icon"><i class="bi bi-patch-check"></i></div>
        <div>
            <div class="metric-label">Final Value</div>
            <div class="metric-value">Rs. {{ number_format((float) $summary['final_maturity_value'], 2) }}</div>
            <div class="metric-trend">Paid + bonus - deductions</div>
        </div>
    </div>
</div>
