<h3 class="form-section-title">Backup Settings</h3>
<div class="row g-3">
    <div class="col-md-4">
        <div class="form-check form-switch mt-4">
            <input type="checkbox" class="form-check-input" id="backup_enabled" name="backup_enabled" value="1" @checked(old('backup_enabled', $values['backup_enabled'] ?? true))>
            <label class="form-check-label" for="backup_enabled">Enable backups</label>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="backup_disk">Backup disk</label>
        <input type="text" class="form-control" id="backup_disk" name="backup_disk" value="{{ old('backup_disk', $values['backup_disk'] ?? 'local') }}">
        <div class="invalid-feedback d-block" data-error-for="backup_disk"></div>
    </div>
</div>
