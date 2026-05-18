<div class="modal fade" id="paymentCancelModal" tabindex="-1" aria-labelledby="paymentCancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" data-ajax-form="payment-cancel">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentCancelModalLabel">Cancel Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="cancellation_reason">Cancellation reason</label>
                    <textarea class="form-control" id="cancellation_reason" name="cancellation_reason" rows="4" required></textarea>
                    <div class="invalid-feedback" data-error-for="cancellation_reason"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-1"></i>Cancel Payment
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
