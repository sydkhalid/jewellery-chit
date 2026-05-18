<div id="documents" class="detail-panel">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4>Documents</h4>
            <p class="text-muted mb-0">Photo, Aadhaar, PAN, and other customer files.</p>
        </div>
    </div>

    @can('customers.documents')
        <form action="{{ route('customers.documents.store', $customer) }}" method="POST" enctype="multipart/form-data" class="document-upload-form mb-4" data-ajax-form="customer-document">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="document_type">Document type</label>
                    <select class="form-select" id="document_type" name="document_type" required>
                        <option value="">Select</option>
                        <option value="photo">Photo</option>
                        <option value="aadhaar">Aadhaar</option>
                        <option value="pan">PAN</option>
                        <option value="other">Other</option>
                    </select>
                    <div class="invalid-feedback" data-error-for="document_type"></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="document_number">Document number</label>
                    <input type="text" class="form-control" id="document_number" name="document_number">
                    <div class="invalid-feedback" data-error-for="document_number"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="file_path">File</label>
                    <input type="file" class="form-control" id="file_path" name="file_path" accept=".pdf,image/*" required>
                    <div class="invalid-feedback" data-error-for="file_path"></div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-upload me-1"></i>Upload
                    </button>
                </div>
            </div>
        </form>
    @endcan

    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Number</th>
                    <th>Status</th>
                    <th>Uploaded</th>
                    <th class="text-end">File</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customer->documents as $document)
                    <tr>
                        <td>{{ ucfirst(str_replace('_', ' ', $document->document_type)) }}</td>
                        <td>{{ $document->document_number ?: '-' }}</td>
                        <td><span class="badge text-bg-light">{{ ucfirst($document->status) }}</span></td>
                        <td>{{ optional($document->created_at)->format('d M Y') }}</td>
                        <td class="text-end">
                            <a href="{{ asset('storage/'.$document->file_path) }}" target="_blank" class="btn btn-sm btn-light">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No documents uploaded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
