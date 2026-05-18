<div class="modal fade" id="sendMessageModal" tabindex="-1" aria-labelledby="sendMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form
                action="{{ route('messages.send-whatsapp') }}"
                method="POST"
                data-message-send-form
                data-whatsapp-url="{{ route('messages.send-whatsapp') }}"
                data-sms-url="{{ route('messages.send-sms') }}"
            >
                @csrf
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="sendMessageModalLabel">Send Message</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="message_customer_id">Customer</label>
                            <select class="form-select" id="message_customer_id" name="customer_id" data-message-customer>
                                <option value="">No customer selected</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}" data-mobile="{{ $customer->mobile }}">
                                        {{ $customer->customer_code }} - {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback d-block" data-error-for="customer_id"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="message_channel">Channel</label>
                            <select class="form-select" id="message_channel" name="channel" data-message-channel>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="sms">SMS</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="message_type">Message type</label>
                            <select class="form-select" id="message_type" name="message_type" required>
                                @foreach ($messageTypes as $type)
                                    <option value="{{ $type }}">{{ str($type)->replace('_', ' ')->title() }}</option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback d-block" data-error-for="message_type"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="message_mobile">Mobile</label>
                            <input type="text" class="form-control" id="message_mobile" name="mobile" maxlength="20">
                            <div class="invalid-feedback d-block" data-error-for="mobile"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="message_title">Title</label>
                            <input type="text" class="form-control" id="message_title" name="title" maxlength="255">
                            <div class="invalid-feedback d-block" data-error-for="title"></div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="message_body">Message</label>
                            <textarea class="form-control" id="message_body" name="message" rows="5" required></textarea>
                            <div class="invalid-feedback d-block" data-error-for="message"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-1"></i>Send
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
