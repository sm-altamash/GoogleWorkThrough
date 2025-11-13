<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected GoogleClientService $googleClientService;

    public function __construct(GoogleClientService $googleClientService)
    {
        $this->googleClientService = $googleClientService;
    }


    public function listFiles(User $user): array
    {
        $client = $this->googleClientService->getClientForUser($user);
        $driveService = new Drive($client);

        try {
            $results = $driveService->files->listFiles([
                'fields' => 'nextPageToken, files(id, name, mimeType, webViewLink)',
            ]);
            return $results->getFiles() ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to list Google Drive files', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }


    public function uploadFile(User $user, string $filePath, string $fileName): array
    {
        $client = $this->googleClientService->getClientForUser($user);
        $driveService = new Drive($client);

        $file = new \Google\Service\Drive\DriveFile([
            'name' => $fileName,
        ]);

        try {
            $createdFile = $driveService->files->create(
                $file,
                [
                    'data' => fopen($filePath, 'rb'),
                    'mimeType' => mime_content_type($filePath),
                    'uploadType' => 'media',
                    'fields' => 'id, webViewLink'
                ]
            );
            return [
                'id' => $createdFile->getId(),
                'link' => $createdFile->getWebViewLink()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to upload to Google Drive', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }




    /**
     * Upload backup file to Google Drive
     * 
     * WHY SEPARATE METHOD?
     * - Specific handling for large backup files
     * - Different MIME type handling
     * - Can add backup-specific folder organization
     * 
     * @param User $user User who owns the Google Drive
     * @param string $filePath Local path to backup file
     * @param string $fileName Name for file in Drive
     * @param string|null $folderId Optional: Google Drive folder ID
     * @return array ['id' => string, 'link' => string, 'size' => int]
     */
    public function uploadBackupFile(User $user, string $filePath, string $fileName, ?string $folderId = null): array
    {
        $client = $this->googleClientService->getClientForUser($user);
        $driveService = new Drive($client);
    
        // Create file metadata
        // WHY METADATA? Tells Google Drive about the file properties
        $fileMetadata = new \Google\Service\Drive\DriveFile([
            'name' => $fileName,
            'description' => 'Database backup created on ' . now()->toDateTimeString(),
        ]);
    
        // If folder specified, set parent
        // WHY FOLDERS? Organizes backups in Google Drive
        if ($folderId) {
            $fileMetadata->setParents([$folderId]);
        }
    
        try {
            // Upload with chunked upload for large files
            // WHY CHUNKED? Handles large files without memory issues
            $client->setDefer(true);
            
            $request = $driveService->files->create(
                $fileMetadata,
                [
                    'fields' => 'id, webViewLink, size, createdTime'
                ]
            );
    
            // Create media object
            $media = new \Google_Http_MediaFileUpload(
                $client,
                $request,
                mime_content_type($filePath),
                null,
                true,
                1024 * 1024 // 1MB chunk size
            );
            
            $media->setFileSize(filesize($filePath));
    
            // Upload in chunks
            $handle = fopen($filePath, 'rb');
            $uploadedFile = false;
            
            while (!$uploadedFile && !feof($handle)) {
                $chunk = fread($handle, 1024 * 1024);
                $uploadedFile = $media->nextChunk($chunk);
            }
            
            fclose($handle);
            $client->setDefer(false);
    
            if (!$uploadedFile) {
                throw new \Exception('File upload incomplete');
            }
    
            Log::info('Backup uploaded to Google Drive', [
                'user_id' => $user->id,
                'file_id' => $uploadedFile->getId(),
                'filename' => $fileName
            ]);
    
            return [
                'id' => $uploadedFile->getId(),
                'link' => $uploadedFile->getWebViewLink(),
                'size' => $uploadedFile->getSize(),
                'created_at' => $uploadedFile->getCreatedTime()
            ];
    
        } catch (\Exception $e) {
            Log::error('Failed to upload backup to Google Drive', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Create or get backup folder in Google Drive
     * 
     * WHY? Keeps backups organized in dedicated folder
     * 
     * @param User $user
     * @param string $folderName
     * @return string Folder ID
     */
    public function getOrCreateBackupFolder(User $user, string $folderName = 'Database Backups'): string
    {
        $client = $this->googleClientService->getClientForUser($user);
        $driveService = new Drive($client);
    
        try {
            // Search for existing folder
            $response = $driveService->files->listFiles([
                'q' => "name='{$folderName}' and mimeType='application/vnd.google-apps.folder' and trashed=false",
                'fields' => 'files(id, name)'
            ]);
    
            $files = $response->getFiles();
    
            // If folder exists, return its ID
            if (count($files) > 0) {
                return $files[0]->getId();
            }
    
            // Create new folder
            $folderMetadata = new \Google\Service\Drive\DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);
    
            $folder = $driveService->files->create($folderMetadata, [
                'fields' => 'id'
            ]);
    
            Log::info('Created backup folder in Google Drive', [
                'folder_id' => $folder->getId(),
                'folder_name' => $folderName
            ]);
    
            return $folder->getId();
    
        } catch (\Exception $e) {
            Log::error('Failed to create backup folder', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
