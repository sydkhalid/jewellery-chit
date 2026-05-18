<div class="modal fade" id="pendingDueFollowupModal" tabindex="-1" aria-labelledby="pendingDueFollowupModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form class="modal-content" method="POST">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="pendingDueFollowupModalLabel">Update Follow-up</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="followup_status">Follow-up status</label>
                    <select id="followup_status" name="followup_status" class="form-select" required>
                        <option value="pending">Pending</option>
                        <option value="called">Called</option>
                        <option value="promised">Promised</option>
                        <option value="not_reachable">Not reachable</option>
                        <option value="paid">Paid</option>
                        <option value="closed">Closed</option>
                    </select>
                    <div class="invalid-feedback" data-error-for="followup_status"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="promise_to_pay_date">Promise-to-pay date</label>
                    <input type="date" id="promise_to_pay_date" name="promise_to_pay_date" class="form-control">
                    <div class="invalid-feedback" data-error-for="promise_to_pay_date"></div>
                </div>
                <div class="mb-0">
                    <label class="form-label" for="remarks">Remarks</label>
                    <textarea id="remarks" name="remarks" class="form-control" rows="4"></textarea>
                    <div class="invalid-feedback" data-error-for="remarks"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save Follow-up</button>
            </div>
        </form>
    </div>
</div>
