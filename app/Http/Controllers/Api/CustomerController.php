<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CustomerDocumentRequest;
use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerController extends BaseApiController
{
    public function __construct(
        private readonly CustomerRepository $customers,
        private readonly CustomerService $customerService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);
        $customers = $this->customers->query()
            ->with('nominee')
            ->withCount(['documents', 'enrollments'])
            ->when($request->input('status'), fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage);

        return $this->sendPaginated($customers, CustomerResource::class, 'Customers fetched successfully');
    }

    public function store(CustomerStoreRequest $request): JsonResponse
    {
        $customer = $this->customerService->createCustomer($request->validated());

        return $this->sendSuccess([
            'customer' => new CustomerResource($customer->loadMissing('nominee')),
        ], 'Customer created successfully', 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        return $this->sendSuccess([
            'customer' => new CustomerResource($customer->load(['nominee', 'documents'])->loadCount(['documents', 'enrollments'])),
        ], 'Customer fetched successfully');
    }

    public function update(CustomerUpdateRequest $request, Customer $customer): JsonResponse
    {
        $customer = $this->customerService->updateCustomer($customer, $request->validated());

        return $this->sendSuccess([
            'customer' => new CustomerResource($customer->loadMissing('nominee')),
        ], 'Customer updated successfully');
    }

    public function destroy(Customer $customer): JsonResponse
    {
        try {
            $this->customerService->deleteCustomer($customer);
        } catch (ValidationException $exception) {
            return $this->sendValidationError($exception);
        }

        return $this->sendSuccess([], 'Customer deleted successfully');
    }

    public function uploadDocument(CustomerDocumentRequest $request, Customer $customer): JsonResponse
    {
        $document = $this->customerService->uploadCustomerDocument($customer, $request->validated());

        return $this->sendSuccess([
            'document' => $document,
        ], 'Customer document uploaded successfully', 201);
    }

    public function ledger(Customer $customer): JsonResponse
    {
        return $this->sendSuccess($this->customerService->getCustomerLedger($customer), 'Customer ledger fetched successfully');
    }

    public function paymentHistory(Customer $customer): JsonResponse
    {
        return $this->sendSuccess($this->customerService->getCustomerPaymentHistory($customer), 'Customer payment history fetched successfully');
    }

    public function outstanding(Customer $customer): JsonResponse
    {
        return $this->sendSuccess($this->customerService->getCustomerOutstanding($customer), 'Customer outstanding fetched successfully');
    }
}
