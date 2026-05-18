<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BackupLog;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function __construct(
        private readonly BackupService $backups
    ) {
    }

    public function index(): View
    {
        return view('backups.index', [
            'backups' => $this->backups->listBackups(),
            'logs' => BackupLog::query()->with('creator')->latest()->limit(20)->get(),
        ]);
    }

    public function create(): JsonResponse|RedirectResponse
    {
        try {
            $log = $this->backups->createBackup();
        } catch (RuntimeException $exception) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'data' => [],
                ], 500);
            }

            return back()->with('error', $exception->getMessage());
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'data' => [
                    'backup_log_id' => $log->id,
                    'reload' => true,
                ],
            ]);
        }

        return back()->with('success', 'Backup created successfully');
    }

    public function download(string $backup): StreamedResponse
    {
        $file = $this->backups->downloadBackup($backup);

        return Storage::disk($file['disk'])->download($file['path'], $file['name']);
    }

    public function delete(string $backup): JsonResponse|RedirectResponse
    {
        try {
            $this->backups->deleteBackup($backup);
        } catch (RuntimeException $exception) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage(),
                    'data' => [],
                ], 404);
            }

            return back()->with('error', $exception->getMessage());
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully',
                'data' => [
                    'reload' => true,
                ],
            ]);
        }

        return back()->with('success', 'Backup deleted successfully');
    }
}
