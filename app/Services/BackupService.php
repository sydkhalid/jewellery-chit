<?php

namespace App\Services;

use App\Models\BackupLog;
use App\Models\ShopSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class BackupService
{
    public function __construct(
        private readonly AuditLogService $audits,
        private readonly ActivityLogService $activities
    ) {
    }

    public function createBackup(): BackupLog
    {
        $disk = $this->disk();
        $this->configureBackup($disk);

        try {
            $exitCode = Artisan::call('backup:run', [
                '--only-db' => true,
                '--disable-notifications' => true,
            ]);

            if ($exitCode !== 0) {
                throw new RuntimeException(trim(Artisan::output()) ?: 'Backup command failed.');
            }

            $backup = $this->latestBackupFile();

            return $this->logBackupResult([
                'backup_name' => $backup['name'] ?? $this->backupName(),
                'file_path' => $backup['path'] ?? null,
                'disk' => $disk,
                'size' => $backup['size'] ?? null,
                'status' => 'success',
                'message' => 'Database backup completed successfully.',
            ]);
        } catch (Throwable $exception) {
            $log = $this->logBackupResult([
                'backup_name' => $this->backupName(),
                'file_path' => null,
                'disk' => $disk,
                'size' => null,
                'status' => 'failed',
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException($log->message ?? 'Backup failed.', previous: $exception);
        }
    }

    /**
     * @return array<int, array{name: string, path: string, disk: string, size: int, date: Carbon}>
     */
    public function listBackups(): array
    {
        $disk = $this->disk();
        $backupName = $this->backupName();
        $storage = Storage::disk($disk);
        $files = $storage->exists($backupName) ? $storage->allFiles($backupName) : $storage->allFiles();
        $backups = [];

        foreach ($files as $file) {
            if (! str_ends_with(strtolower($file), '.zip')) {
                continue;
            }

            $backups[] = [
                'name' => basename($file),
                'path' => $file,
                'disk' => $disk,
                'size' => (int) $storage->size($file),
                'date' => Carbon::createFromTimestamp($storage->lastModified($file)),
            ];
        }

        usort($backups, fn (array $first, array $second): int => $second['date']->timestamp <=> $first['date']->timestamp);

        return $backups;
    }

    /**
     * @return array{name: string, path: string, disk: string, size: int, date: Carbon}
     */
    public function downloadBackup(string $backup): array
    {
        $file = $this->findBackup($backup);

        $this->activities->log('backup', 'download', "Backup {$file['name']} downloaded.");
        $this->audits->logAction('backup', 'download', "Backup {$file['name']} downloaded.");

        return $file;
    }

    public function deleteBackup(string $backup): void
    {
        $file = $this->findBackup($backup);
        Storage::disk($file['disk'])->delete($file['path']);

        $this->logBackupResult([
            'backup_name' => $file['name'],
            'file_path' => $file['path'],
            'disk' => $file['disk'],
            'size' => $file['size'],
            'status' => 'success',
            'message' => 'Backup deleted successfully.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function logBackupResult(array $data): BackupLog
    {
        $log = BackupLog::create([
            'backup_name' => $data['backup_name'],
            'file_path' => $data['file_path'] ?? null,
            'disk' => $data['disk'] ?? $this->disk(),
            'size' => $data['size'] ?? null,
            'status' => $data['status'],
            'message' => $data['message'] ?? null,
            'created_by' => Auth::id(),
        ]);

        $this->activities->log('backup', $log->status, $log->message);
        $this->audits->log('backup', $log->status, $log, [], $log->toArray(), $log->message);

        return $log;
    }

    /**
     * @return array{name: string, path: string, disk: string, size: int, date: Carbon}|null
     */
    private function latestBackupFile(): ?array
    {
        return $this->listBackups()[0] ?? null;
    }

    /**
     * @return array{name: string, path: string, disk: string, size: int, date: Carbon}
     */
    private function findBackup(string $backup): array
    {
        $safeName = basename($backup);

        foreach ($this->listBackups() as $file) {
            if ($file['name'] === $safeName) {
                return $file;
            }
        }

        throw new RuntimeException('Backup file not found.');
    }

    private function configureBackup(string $disk): void
    {
        config([
            'backup.backup.name' => $this->backupName(),
            'backup.backup.destination.disks' => [$disk],
            'backup.backup.destination.filename_prefix' => 'jewellery-chit-',
        ]);
    }

    private function disk(): string
    {
        return (string) ShopSetting::getByKey('backup_disk', config('backup.backup.destination.disks.0', 'local'));
    }

    private function backupName(): string
    {
        return (string) config('backup.backup.name', config('app.name', 'jewellery-chit'));
    }
}
