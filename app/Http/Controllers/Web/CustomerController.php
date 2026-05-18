<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerDocumentRequest;
use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerRepository $customers,
        private readonly CustomerService $customerService
    ) {
    }

    public function index(): View
    {
        return view('customers.index');
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();

        return DataTables::eloquent($this->customers->getForDataTable($request->only('status')))
            ->addColumn('status_badge', fn (Customer $customer): string => sprintf(
                '<span class="badge rounded-pill text-bg-%s">%s</span>',
                $customer->status === 'active' ? 'success' : 'secondary',
                ucfirst($customer->status)
            ))
            ->addColumn('actions', fn (Customer $customer): string => $this->actionButtons($customer, $user))
            ->editColumn('created_at', fn (Customer $customer): string => optional($customer->created_at)->format('d M Y'))
            ->rawColumns(['status_badge', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        return view('customers.create', [
            'customer' => new Customer(['status' => 'active']),
        ]);
    }

    public function store(CustomerStoreRequest $request): JsonResponse|RedirectResponse
    {
        $customer = $this->customerService->createCustomer($request->validated());

        return $this->successResponse($request, 'Customer created successfully', $customer, route('customers.show', $customer));
    }

    public function show(Customer $customer): View
    {
        return view('customers.show', $this->customerService->getCustomerProfile($customer) + [
            'activeTab' => request('tab', 'profile'),
        ]);
    }

    public function edit(Customer $customer): View
    {
        $customer->load('nominee');

        return view('customers.edit', [
            'customer' => $customer,
        ]);
    }

    public function update(CustomerUpdateRequest $request, Customer $customer): JsonResponse|RedirectResponse
    {
        $customer = $this->customerService->updateCustomer($customer, $request->validated());

        return $this->successResponse($request, 'Customer updated successfully', $customer, route('customers.show', $customer));
    }

    public function destroy(Request $request, Customer $customer): JsonResponse|RedirectResponse
    {
        try {
            $this->customerService->deleteCustomer($customer);
        } catch (ValidationException $exception) {
            return $this->validationErrorResponse($request, $exception);
        }

        return $this->successResponse($request, 'Customer deleted successfully', null, route('customers.index'));
    }

    public function deactivate(Request $request, Customer $customer): JsonResponse|RedirectResponse
    {
        $customer = $this->customerService->deactivateCustomer($customer);

        return $this->successResponse($request, 'Customer deactivated successfully', $customer, route('customers.index'));
    }

    public function uploadDocument(CustomerDocumentRequest $request, Customer $customer): JsonResponse
    {
        $document = $this->customerService->uploadCustomerDocument($customer, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Customer document uploaded successfully',
            'data' => [
                'document' => $document,
                'redirect' => route('customers.show', $customer),
            ],
        ]);
    }

    public function ledger(Customer $customer): View
    {
        return view('customers.show', $this->customerService->getCustomerProfile($customer) + [
            'activeTab' => 'ledger',
        ]);
    }

    public function paymentHistory(Customer $customer): View
    {
        return view('customers.show', $this->customerService->getCustomerProfile($customer) + [
            'activeTab' => 'payments',
        ]);
    }

    public function outstanding(Customer $customer): View
    {
        return view('customers.show', $this->customerService->getCustomerProfile($customer) + [
            'activeTab' => 'outstanding',
        ]);
    }

    private function actionButtons(Customer $customer, mixed $user): string
    {
        $buttons = [];

        if ($user?->can('customers.view')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('customers.show', $customer).'" title="View"><i class="bi bi-eye"></i></a>';
        }

        if ($user?->can('customers.edit')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('customers.edit', $customer).'" title="Edit"><i class="bi bi-pencil"></i></a>';
        }

        if ($user?->can('customers.documents')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('customers.show', ['customer' => $customer, 'tab' => 'documents']).'#documents" title="Documents"><i class="bi bi-folder2-open"></i></a>';
        }

        if ($user?->can('ledger.customer')) {
            $buttons[] = '<a class="btn btn-sm btn-light" href="'.route('customers.ledger', $customer).'" title="Ledger"><i class="bi bi-journal-text"></i></a>';
        }

        if ($customer->status === 'active' && $user?->can('customers.deactivate')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-warning" data-customer-action="deactivate" data-url="'.route('customers.deactivate', $customer).'" data-method="PATCH" title="Deactivate"><i class="bi bi-person-dash"></i></button>';
        }

        if ((int) $customer->enrollments_count === 0 && $user?->can('customers.delete')) {
            $buttons[] = '<button type="button" class="btn btn-sm btn-danger" data-customer-action="delete" data-url="'.route('customers.destroy', $customer).'" data-method="DELETE" title="Delete"><i class="bi bi-trash"></i></button>';
        }

        return '<div class="d-flex flex-wrap gap-1">'.$this->joinButtons($buttons).'</div>';
    }

    /**
     * @param  array<int, string>  $buttons
     */
    private function joinButtons(array $buttons): string
    {
        return implode('', $buttons);
    }

    private function successResponse(Request $request, string $message, ?Customer $customer = null, ?string $redirect = null): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'customer' => $customer ? new CustomerResource($customer->loadMissing('nominee')) : null,
                    'redirect' => $redirect,
                ],
            ]);
        }

        return redirect($redirect ?? route('customers.index'))->with('success', $message);
    }

    private function validationErrorResponse(Request $request, ValidationException $exception): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data' => [
                    'errors' => $exception->errors(),
                ],
            ], 422);
        }

        return back()->withErrors($exception->errors())->withInput();
    }
}
