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
}