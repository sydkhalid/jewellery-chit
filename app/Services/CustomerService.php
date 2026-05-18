<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Repositories\CustomerRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    public function __construct(
        private readonly CustomerRepository $customers
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCustomer(array $data): Customer
    {
        return DB::transaction(function () use ($data): Customer {
            $customerData = $this->normalizeCustomerData($data);
            $customerData['customer_code'] = $this->generateCustomerCode();
            $customerData['status'] = $customerData['status'] ?? 'active';
            $customerData['created_by'] = Auth::id();
            $customerData['updated_by'] = Auth::id();

            if (($data['photo'] ?? null) instanceof UploadedFile) {
                $customerData['photo'] = $data['photo']->store('customers/photos', 'public');
            }

            $customer = $this->customers->create($customerData);
            $this->syncNominee($customer, $data['nominee'] ?? []);
            $customer->load('nominee');

            $this->logCustomerAction($customer, 'create', 'created', null, $customer->toArray());

            return $customer;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCustomer(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data): Customer {
            $oldValues = $customer->load('nominee')->toArray();
            $customerData = $this->normalizeCustomerData($data);
            $customerData['updated_by'] = Auth::id();

            if (($data['photo'] ?? null) instanceof UploadedFile) {
                $customerData['photo'] = $data['photo']->store('customers/photos', 'public');
            }

            $customer = $this->customers->update($customer, $customerData);
            $this->syncNominee($customer, $data['nominee'] ?? []);
            $customer->load('nominee');

            $this->logCustomerAction($customer, 'update', 'updated', $oldValues, $customer->toArray());

            return $customer;
        });
    }

    public function deleteCustomer(Customer $customer): bool
    {
        return DB::transaction(function () use ($customer): bool {
            if ($customer->enrollments()->exists()) {
                throw ValidationException::withMessages([
                    'customer' => 'Customer has chit enrollments. Deactivate the customer instead of deleting.',
                ]);
            }

            $oldValues = $customer->toArray();
            $customer->update(['deleted_by' => Auth::id()]);
            $deleted = $this->customers->delete($customer);

            $this->logCustomerAction($customer, 'delete', 'deleted', $oldValues, null);

            return $deleted;
        });
    }

    public function deactivateCustomer(Customer $customer): Customer
    {
        return DB::transaction(function () use ($customer): Customer {
            $oldValues = $customer->toArray();
            $customer = $this->customers->update($customer, [
                'status' => 'inactive',
                'updated_by' => Auth::id(),
            ]);

            $this->logCustomerAction($customer, 'deactivate', 'deactivated', $oldValues, $customer->toArray());

            return $customer;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function uploadCustomerDocument(Customer $customer, array $data): CustomerDocument
    {
        return DB::transaction(function () use ($customer, $data): CustomerDocument {
            /** @var UploadedFile $file */
            $file = $data['file_path'];

            $document = $this->customers->uploadDocument($customer, [
                'document_type' => $data['document_type'],
                'document_number' => $data['document_number'] ?? null,
                'file_path' => $file->store('customers/documents', 'public'),
                'status' => $data['status'] ?? 'active',
                'uploaded_by' => Auth::id(),
            ]);

            $this->logCustomerAction(
                $customer,
                'document_upload',
                'document upload',
                null,
                $document->toArray(),
                $document
            );

            return $document;
        });
    }

    public function generateCustomerCode(): string
    {
        $nextId = (int) Customer::withTrashed()->max('id') + 1;

        do {
            $code = 'CUS'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
            $nextId++;
        } while (Customer::withTrashed()->where('customer_code', $code)->exists());

        return $code;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomerProfile(Customer $customer): array
    {
        $customer->load([
            'nominee',
            'documents' => fn ($query) => $query->latest(),
            'enrollments.scheme',
            'payments.paymentMode',
            'ledgers',
        ]);

        return [
            'customer' => $customer,
            'ledger' => $this->getCustomerLedger($customer),
            'paymentHistory' => $this->getCustomerPaymentHistory($customer),
            'outstanding' => $this->getCustomerOutstanding($customer),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomerLedger(Customer $customer): array
    {
        $entries = $customer->ledgers()
            ->with('enrollment')
            ->latest('transaction_date')
            ->latest('id')
            ->get();

        return [
            'entries' => $entries,
            'total_debit' => (float) $entries->sum('debit'),
            'total_credit' => (float) $entries->sum('credit'),
            'closing_balance' => (float) optional($entries->first())->balance,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomerPaymentHistory(Customer $customer): array
    {
        $payments = $customer->payments()
            ->with(['paymentMode', 'receipt', 'enrollment'])
            ->latest('payment_date')
            ->latest('id')
            ->get();

        return [
            'payments' => $payments,
            'total_paid' => (float) $payments->where('status', 'success')->sum('total_amount'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCustomerOutstanding(Customer $customer): array
    {
        $enrollments = $customer->enrollments()
            ->with('scheme')
            ->whereIn('status', ['active', 'defaulted'])
            ->get();

        return [
            'enrollments' => $enrollments,
            'total_outstanding' => (float) $enrollments->sum(fn ($enrollment): float => (float) $enrollment->balance_amount),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeCustomerData(array $data): array
    {
        $customerData = Arr::only($data, [
            'name',
            'mobile',
            'alternate_mobile',
            'email',
            'aadhaar_no',
            'pan_no',
            'address',
            'city',
            'state',
            'pincode',
            'status',
        ]);

        foreach (['alternate_mobile', 'email', 'aadhaar_no', 'pan_no'] as $nullableField) {
            $customerData[$nullableField] = $customerData[$nullableField] ?? null;
        }

        foreach (['city', 'state', 'pincode'] as $textField) {
            $customerData[$textField] = $customerData[$textField] ?? '';
        }

        return $customerData;
    }

    /**
     * @param  array<string, mixed>  $nomineeData
     */
    private function syncNominee(Customer $customer, array $nomineeData): void
    {
        $nomineeData = Arr::only($nomineeData, [
            'name',
            'relationship',
            'mobile',
            'address',
            'aadhaar_no',
        ]);

        $hasNomineeData = collect($nomineeData)->filter(fn ($value): bool => filled($value))->isNotEmpty();

        if (! $hasNomineeData) {
            $customer->nominee()?->delete();

            return;
        }

        $nomineeData['name'] = $nomineeData['name'] ?? '';
        $nomineeData['relationship'] = $nomineeData['relationship'] ?? '';

        $customer->nominee()->updateOrCreate(
            ['customer_id' => $customer->id],
            $nomineeData
        );
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function logCustomerAction(
        Customer $customer,
        string $event,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        Customer|CustomerDocument|null $auditable = null
    ): void {
        $actorId = Auth::id();
        $auditable ??= $customer;

        ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'module' => 'customers',
            'description' => "Customer {$customer->customer_code} {$action}.",
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        AuditLog::create([
            'user_id' => $actorId,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->id,
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
