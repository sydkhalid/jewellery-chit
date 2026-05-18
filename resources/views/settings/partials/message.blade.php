<h3 class="form-section-title">WhatsApp/SMS Settings</h3>
<div class="row g-3">
    <div class="col-md-6">
        <div class="form-check form-switch mb-3">
            <input type="checkbox" class="form-check-input" id="whatsapp_enabled" name="whatsapp_enabled" value="1" @checked(old('whatsapp_enabled', $values['whatsapp_enabled'] ?? false))>
            <label class="form-check-label" for="whatsapp_enabled">Enable WhatsApp</label>
        </div>
        <label class="form-label" for="whatsapp_api_url">WhatsApp API URL</label>
        <input type="url" class="form-control" id="whatsapp_api_url" name="whatsapp_api_url" value="{{ old('whatsapp_api_url', $values['whatsapp_api_url'] ?? '') }}">
        <div class="invalid-feedback d-block" data-error-for="whatsapp_api_url"></div>
        <label class="form-label mt-3" for="whatsapp_api_key">WhatsApp API key</label>
        <input type="text" class="form-control" id="whatsapp_api_key" name="whatsapp_api_key" value="{{ old('whatsapp_api_key', $values['whatsapp_api_key'] ?? '') }}">
        <div class="invalid-feedback d-block" data-error-for="whatsapp_api_key"></div>
    </div>
    <div class="col-md-6">
        <div class="form-check form-switch mb-3">
            <input type="checkbox" class="form-check-input" id="sms_enabled" name="sms_enabled" value="1" @checked(old('sms_enabled', $values['sms_enabled'] ?? false))>
            <label class="form-check-label" for="sms_enabled">Enable SMS</label>
        </div>
        <label class="form-label" for="sms_api_url">SMS API URL</label>
        <input type="url" class="form-control" id="sms_api_url" name="sms_api_url" value="{{ old('sms_api_url', $values['sms_api_url'] ?? '') }}">
        <div class="invalid-feedback d-block" data-error-for="sms_api_url"></div>
        <label class="form-label mt-3" for="sms_api_key">SMS API key</label>
        <input type="text" class="form-control" id="sms_api_key" name="sms_api_key" value="{{ old('sms_api_key', $values['sms_api_key'] ?? '') }}">
        <div class="invalid-feedback d-block" data-error-for="sms_api_key"></div>
    </div>
</div>
