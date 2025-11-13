<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\User;

/**
 * BackupController
 * 
 * Handles database backup and Google Drive upload via API
 * 
 * WHY API CONTROLLER?
 * - Allows cron jobs to trigger backups via HTTP
 * - Can be called from external services
 * - Provides RESTful interface
 * - Returns JSON responses
 */
class BackupController extends Controller
{
    protected DatabaseBackupService $backupService;
    protected GoogleDriveService $driveService;

    public function __construct(
        DatabaseBackupService $backupService,
        GoogleDriveService $driveService
    ) {
        $this->backupService = $backupService;
        $this->driveService = $driveService;
    }

    /**
     * Create database backup and upload to Google Drive
     * 
     * API ENDPOINT: POST /api/backup/create
     * 
     * AUTHENTICATION OPTIONS:
     * 1. Bearer Token (Sanctum)
     * 2. API Key in header
     * 3. User ID in request
     * 
     * REQUEST BODY:
     * {
     *     "user_id": 1,              // Required: User who owns Google Drive
     *     "compress": true,           // Optional: Compress backup
     *     "delete_local": true,       // Optional: Delete local file after upload
     *     "backup_method": "mysqldump" // Optional: mysqldump|laravel
     * }
     * 
     * RESPONSE:
     * {
     *     "success": true,
     *     "message": "Backup completed successfully",
     *     "data": {
     *         "backup_file": "backup_2025-11-13_14-30-45.sql",
     *         "backup_size": "15.5 MB",
     *         "drive_file_id": "1abc...",
     *         "drive_link": "https://drive.google.com/...",
     *         "upload_time": "2.5 seconds"
     *     }
     * }
     */
    public function createAndUpload(Request $request)
    {
        $startTime = microtime(true);

        try {
            // STEP 1: Validate request
            // WHY VALIDATE? Ensures we have required data
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'compress' => 'boolean',
                'delete_local' => 'boolean',
                'backup_method' => 'in:mysqldump,laravel'
            ]);

            // STEP 2: Get user
            // WHY? Need user's Google token for Drive access
            $user = User::findOrFail($validated['user_id']);

            // STEP 3: Check if user has Google token
            // WHY? Can't upload without Drive access
            if (!$user->googleToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has not connected Google Drive'
                ], 400);
            }

            // STEP 4: Create database backup
            Log::info('Starting database backup', ['user_id' => $user->id]);

            $compress = $request->input('compress', false);
            $backupMethod = $request->input('backup_method', 'mysqldump');

            // Choose backup method
            if ($compress) {
                $backupResult = $this->backupService->createCompressedBackup();
            } elseif ($backupMethod === 'laravel') {
                $backupResult = $this->backupService->createBackupUsingLaravel();
            } else {
                $backupResult = $this->backupService->createBackupUsingMysqldump();
            }

            if (!$backupResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $backupResult['message']
                ], 500);
            }

            Log::info('Database backup created', [
                'filename' => $backupResult['filename'],
                'size' => $backupResult['size_human']
            ]);

            // STEP 5: Get or create backup folder in Drive
            $folderId = $this->driveService->getOrCreateBackupFolder($user);

            // STEP 6: Upload to Google Drive
            Log::info('Uploading backup to Google Drive');

            $driveResult = $this->driveService->uploadBackupFile(
                $user,
                $backupResult['path'],
                $backupResult['filename'],
                $folderId
            );

            Log::info('Backup uploaded to Google Drive', [
                'drive_file_id' => $driveResult['id']
            ]);

            // STEP 7: Clean up local file if requested
            $deleteLocal = $request->input('delete_local', true);
            if ($deleteLocal && File::exists($backupResult['path'])) {
                File::delete($backupResult['path']);
                Log::info('Local backup file deleted');
            }

            // STEP 8: Clean old local backups
            $this->backupService->cleanOldBackups(7);

            // Calculate execution time
            $executionTime = round(microtime(true) - $startTime, 2);

            // STEP 9: Return success response
            return response()->json([
                'success' => true,
                'message' => 'Backup completed and uploaded successfully',
                'data' => [
                    'backup_file' => $backupResult['filename'],
                    'backup_size' => $backupResult['size_human'],
                    'backup_size_bytes' => $backupResult['size'],
                    'drive_file_id' => $driveResult['id'],
                    'drive_link' => $driveResult['link'],
                    'folder_id' => $folderId,
                    'execution_time' => "{$executionTime} seconds",
                    'timestamp' => now()->toDateTimeString(),
                    'local_file_deleted' => $deleteLocal
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Backup API error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get backup history from Google Drive
     * 
     * API ENDPOINT: GET /api/backup/history
     * 
     * QUERY PARAMS:
     * - user_id: Required
     * - limit: Optional (default 10)
     */
    public function history(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'limit' => 'integer|min:1|max:100'
            ]);

            $user = User::findOrFail($request->user_id);
            $limit = $request->input('limit', 10);

            if (!$user->googleToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has not connected Google Drive'
                ], 400);
            }

            $folderId = $this->driveService->getOrCreateBackupFolder($user);
            $client = app(GoogleClientService::class)->getClientForUser($user);
            $driveService = new \Google\Service\Drive($client);

            // List files in backup folder
            $response = $driveService->files->listFiles([
                'q' => "'{$folderId}' in parents and trashed=false",
                'orderBy' => 'createdTime desc',
                'pageSize' => $limit,
                'fields' => 'files(id, name, size, createdTime, webViewLink)'
            ]);

            $files = $response->getFiles();
            $backups = [];

            foreach ($files as $file) {
                $backups[] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'size' => $this->formatBytes($file->getSize()),
                    'size_bytes' => $file->getSize(),
                    'created_at' => $file->getCreatedTime(),
                    'link' => $file->getWebViewLink()
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $backups,
                'count' => count($backups)
            ]);

        } catch (\Exception $e) {
            Log::error('Backup history error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch backup history'
            ], 500);
        }
    }

    /**
     * Helper: Format bytes
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
