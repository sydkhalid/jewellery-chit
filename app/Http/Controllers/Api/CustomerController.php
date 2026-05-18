<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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

class CustomerController extends Controller
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

        return response()->json([
            'success' => true,
            'message' => 'Customers fetched successfully',
            'data' => [
                'customers' => CustomerResource::collection($customers->getCollection()),
                'pagination' => [
                    'current_page' => $customers->currentPage(),
                    'last_page' => $customers->lastPage(),
                    'per_page' => $customers->perPage(),
                    'total' => $customers->total(),
                ],
            ],
        ]);
    }

    public function store(CustomerStoreRequest $request): JsonResponse
    {
        $customer = $this->customerService->createCustomer($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully',
            'data' => [
                'customer' => new CustomerResource($customer->loadMissing('nominee')),
            ],
        ], 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Customer fetched successfully',
            'data' => [
                'customer' => new CustomerResource($customer->load(['nominee', 'documents'])->loadCount(['documents', 'enrollments'])),
            ],
        ]);
    }

    public function update(CustomerUpdateRequest $request, Customer $customer): JsonResponse
    {
        $customer = $this->customerService->updateCustomer($customer, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data' => [
                'customer' => new CustomerResource($customer->loadMissing('nominee')),
            ],
        ]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        try {
            $this->customerService->deleteCustomer($customer);
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data' => [
                    'errors' => $exception->errors(),
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully',
            'data' => [],
        ]);
    }

    public function uploadDocument(CustomerDocumentRequest $request, Customer $customer): JsonResponse
    {
        $document = $this->customerService->uploadCustomerDocument($customer, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Customer document uploaded successfully',
            'data' => [
                'document' => $document,
            ],
        ], 201);
    }

    public function ledger(Customer $customer): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Customer ledger fetched successfully',
            'data' => $this->customerService->getCustomerLedger($customer),
        ]);
    }

    public function paymentHistory(Customer $customer): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Customer payment history fetched successfully',
            'data' => $this->customerService->getCustomerPaymentHistory($customer),
        ]);
    }

    public function outstanding(Customer $customer): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Customer outstanding fetched successfully',
            'data' => $this->customerService->getCustomerOutstanding($customer),
        ]);
    }
}
