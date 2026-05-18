<div class="row g-4">
    <div class="col-md-3">
        <label class="form-label" for="rate_date">Rate date</label>
        <input type="date" class="form-control" id="rate_date" name="rate_date" value="{{ old('rate_date', optional($goldRate->rate_date)->toDateString() ?? today()->toDateString()) }}" required>
        <div class="invalid-feedback" data-error-for="rate_date"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="gold_22k">22K gold rate</label>
        <input type="number" step="0.01" min="1" class="form-control" id="gold_22k" name="gold_22k" value="{{ old('gold_22k', $goldRate->gold_22k) }}" required>
        <div class="invalid-feedback" data-error-for="gold_22k"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="gold_24k">24K gold rate</label>
        <input type="number" step="0.01" min="1" class="form-control" id="gold_24k" name="gold_24k" value="{{ old('gold_24k', $goldRate->gold_24k) }}" required>
        <div class="invalid-feedback" data-error-for="gold_24k"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="silver_rate">Silver rate</label>
        <input type="number" step="0.01" min="0" class="form-control" id="silver_rate" name="silver_rate" value="{{ old('silver_rate', $goldRate->silver_rate) }}">
        <div class="invalid-feedback" data-error-for="silver_rate"></div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="status">Status</label>
        <select class="form-select" id="status" name="status">
            @foreach (['pending', 'approved', 'rejected'] as $status)
                <option value="{{ $status }}" @selected(old('status', $goldRate->status ?? 'pending') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback" data-error-for="status"></div>
    </div>

    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check form-switch">
            <input type="hidden" name="rate_locked" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="rate_locked" name="rate_locked" value="1" @checked(old('rate_locked', $goldRate->rate_locked))>
            <label class="form-check-label" for="rate_locked">Lock rate</label>
        </div>
        <div class="invalid-feedback" data-error-for="rate_locked"></div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('gold-rates.index') }}" class="btn btn-light">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check2 me-1"></i>Save Rate
        </button>
    </div>
</div>
