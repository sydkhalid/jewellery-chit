<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller
{
    public function index(): View
    {
        return view('activity-logs.index', [
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'actions' => ActivityLog::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'modules' => ActivityLog::query()->select('module')->distinct()->orderBy('module')->pluck('module'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = ActivityLog::query()
            ->with('user')
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->input('user_id')))
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->input('action')))
            ->when($request->filled('module'), fn ($query) => $query->where('module', $request->input('module')))
            ->when($request->filled('from_date'), fn ($query) => $query->whereDate('created_at', '>=', $request->input('from_date')))
            ->when($request->filled('to_date'), fn ($query) => $query->whereDate('created_at', '<=', $request->input('to_date')));

        return DataTables::eloquent($query)
            ->addColumn('user_name', fn (ActivityLog $activityLog): string => $activityLog->user?->name ?? 'System')
            ->addColumn('module_name', fn (ActivityLog $activityLog): string => str($activityLog->module)->replace('_', ' ')->headline()->toString())
            ->addColumn('actions', fn (ActivityLog $activityLog): string => '<a class="btn btn-sm btn-light" href="'.route('activity-logs.show', $activityLog).'" title="View"><i class="bi bi-eye"></i></a>')
            ->editColumn('created_at', fn (ActivityLog $activityLog): string => $activityLog->created_at?->format('d M Y, h:i A') ?? '-')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function show(ActivityLog $activityLog): View
    {
        return view('activity-logs.show', [
            'activityLog' => $activityLog->load('user'),
        ]);
    }
}
