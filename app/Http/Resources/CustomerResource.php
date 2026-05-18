<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CustomerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_code' => $this->customer_code,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'alternate_mobile' => $this->alternate_mobile,
            'email' => $this->email,
            'photo' => $this->photo,
            'photo_url' => $this->photo ? Storage::disk('public')->url($this->photo) : null,
            'aadhaar_no' => $this->aadhaar_no,
            'pan_no' => $this->pan_no,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'pincode' => $this->pincode,
            'full_address' => $this->full_address,
            'status' => $this->status,
            'nominee' => $this->whenLoaded('nominee', fn () => $this->nominee ? [
                'id' => $this->nominee->id,
                'name' => $this->nominee->name,
                'relationship' => $this->nominee->relationship,
                'mobile' => $this->nominee->mobile,
                'address' => $this->nominee->address,
                'aadhaar_no' => $this->nominee->aadhaar_no,
            ] : null),
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($document): array => [
                'id' => $document->id,
                'document_type' => $document->document_type,
                'document_number' => $document->document_number,
                'file_path' => $document->file_path,
                'file_url' => Storage::disk('public')->url($document->file_path),
                'status' => $document->status,
                'uploaded_at' => optional($document->created_at)->toDateTimeString(),
            ])->values()),
            'enrollments_count' => $this->whenCounted('enrollments'),
            'documents_count' => $this->whenCounted('documents'),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
