<div class="modal fade" id="enrollmentCancelModal" tabindex="-1" aria-labelledby="enrollmentCancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="#" class="modal-content" data-ajax-form="chit-enrollment-cancel">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="enrollmentCancelModalLabel">Cancel Enrollment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="cancellation_date">Cancellation date</label>
                    <input type="date" class="form-control" id="cancellation_date" name="cancellation_date" value="{{ now()->toDateString() }}" required>
                    <div class="invalid-feedback" data-error-for="cancellation_date"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="reason">Reason</label>
                    <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                    <div class="invalid-feedback" data-error-for="reason"></div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="refund_amount">Refund amount</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="refund_amount" name="refund_amount" value="0">
                        <div class="invalid-feedback" data-error-for="refund_amount"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="deduction_amount">Deduction amount</label>
                        <input type="number" step="0.01" min="0" class="form-control" id="deduction_amount" name="deduction_amount" value="0">
                        <div class="invalid-feedback" data-error-for="deduction_amount"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-warning">Cancel Enrollment</button>
            </div>
        </form>
    </div>
</div>
