<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class AuditLogController extends Controller
{
    public function index(): View
    {
        return view('audit-logs.index', [
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'events' => AuditLog::query()->select('event')->distinct()->orderBy('event')->pluck('event'),
            'modules' => AuditLog::query()->select('auditable_type')->distinct()->orderBy('auditable_type')->pluck('auditable_type'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = AuditLog::query()
            ->with('user')
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->input('user_id')))
            ->when($request->filled('event'), fn ($query) => $query->where('event', $request->input('event')))
            ->when($request->filled('module'), fn ($query) => $query->where('auditable_type', $request->input('module')))
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('from_date')))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('to_date')));

        return DataTables::eloquent($query)
            ->addColumn('user_name', fn (AuditLog $auditLog): string => $auditLog->user?->name ?? 'System')
            ->addColumn('module_name', fn (AuditLog $auditLog): string => $this->moduleName($auditLog->auditable_type))
            ->addColumn('actions', fn (AuditLog $auditLog): string => '<a class="btn btn-sm btn-light" href="'.route('audit-logs.show', $auditLog).'" title="View"><i class="bi bi-eye"></i></a>')
            ->editColumn('created_at', fn (AuditLog $auditLog): string => $auditLog->created_at?->format('d M Y, h:i A') ?? '-')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function show(AuditLog $auditLog): View
    {
        return view('audit-logs.show', [
            'auditLog' => $auditLog->load('user'),
        ]);
    }

    private function moduleName(string $auditableType): string
    {
        $parts = explode('\\', $auditableType);

        return str(end($parts) ?: $auditableType)->headline()->toString();
    }
}
