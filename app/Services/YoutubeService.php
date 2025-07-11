<?php

namespace App\Services;

use Google\Client;
use Google\Service\YouTube;
use Google\Service\YouTube\Video;
use Google\Service\YouTube\VideoSnippet;
use Google\Service\YouTube\VideoStatus;
use Google\Service\YouTube\ThumbnailSetRequest;
use Google\Service\YouTube\Playlist;
use Google\Service\YouTube\PlaylistSnippet;
use Google\Service\YouTube\PlaylistStatus;
use Google\Service\YouTube\PlaylistItem;
use Google\Service\YouTube\PlaylistItemSnippet;
use Google\Service\YouTube\ResourceId;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class YouTubeService
{
    protected GoogleClientService $googleClientService;
    protected YouTube $youtube;

    public function __construct(GoogleClientService $googleClientService)
    {
        $this->googleClientService = $googleClientService;
    }

    /**
     * Get YouTube service instance for user
     */
    protected function getYouTubeService(User $user): YouTube
    {
        $client = $this->googleClientService->getClientForUser($user);
        return new YouTube($client);
    }

    /**
     * Check if user has valid YouTube connection
     */
    public function hasValidConnection(User $user): bool
    {
        try {
            if (!$this->googleClientService->hasValidToken($user)) {
                return false;
            }

            // Test the connection by trying to get user's channel
            $youtube = $this->getYouTubeService($user);
            $channels = $youtube->channels->listChannels('snippet', [
                'mine' => true,
                'maxResults' => 1
            ]);

            return $channels->getItems() !== null && count($channels->getItems()) > 0;
        } catch (\Exception $e) {
            Log::error('YouTube connection test failed:', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get user's YouTube playlists
     */
    public function getUserPlaylists(User $user): array
    {
        try {
            $youtube = $this->getYouTubeService($user);
            
            $playlists = $youtube->playlists->listPlaylists('snippet,status', [
                'mine' => true,
                'maxResults' => 50
            ]);

            Log::info('Retrieved YouTube playlists:', [
                'user_id' => $user->id,
                'playlist_count' => count($playlists->getItems())
            ]);

            return $playlists->getItems() ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to retrieve YouTube playlists:', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Create a new playlist
     */
    public function createPlaylist(User $user, string $title, string $description = '', string $privacy = 'private'): ?Playlist
    {
        try {
            $youtube = $this->getYouTubeService($user);

            // Create playlist snippet
            $playlistSnippet = new PlaylistSnippet();
            $playlistSnippet->setTitle($title);
            $playlistSnippet->setDescription($description);

            // Create playlist status
            $playlistStatus = new PlaylistStatus();
            $playlistStatus->setPrivacyStatus($privacy);

            // Create playlist object
            $playlist = new Playlist();
            $playlist->setSnippet($playlistSnippet);
            $playlist->setStatus($playlistStatus);

            // Insert playlist
            $response = $youtube->playlists->insert('snippet,status', $playlist);

            Log::info('Created new YouTube playlist:', [
                'user_id' => $user->id,
                'playlist_id' => $response->getId(),
                'title' => $title
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to create YouTube playlist:', [
                'user_id' => $user->id,
                'title' => $title,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Upload video to YouTube
     */
    public function uploadVideo(User $user, array $videoData): ?Video
    {
        try {
            $youtube = $this->getYouTubeService($user);

            // Create video snippet
            $snippet = new VideoSnippet();
            $snippet->setTitle($videoData['title']);
            $snippet->setDescription($videoData['description'] ?? '');
            
            // Set tags if provided
            if (!empty($videoData['tags'])) {
                $tags = is_array($videoData['tags']) 
                    ? $videoData['tags'] 
                    : array_map('trim', explode(',', $videoData['tags']));
                $snippet->setTags($tags);
            }

            // Create video status
            $status = new VideoStatus();
            $status->setPrivacyStatus($videoData['privacy'] ?? 'private');

            // Create video object
            $video = new Video();
            $video->setSnippet($snippet);
            $video->setStatus($status);

            // Upload video file
            $videoFile = $videoData['video_file'];
            $chunkSizeBytes = 1 * 1024 * 1024; // 1MB chunks

            // Set up the request
            $insertRequest = $youtube->videos->insert('snippet,status', $video);

            // Create media upload
            $media = new \Google_Http_MediaFileUpload(
                $youtube->getClient(),
                $insertRequest,
                'video/*',
                null,
                true,
                $chunkSizeBytes
            );

            // Set the file to upload
            $media->setFileSize(filesize($videoFile->getRealPath()));

            // Read and upload file in chunks
            $status = false;
            $handle = fopen($videoFile->getRealPath(), 'rb');

            while (!$status && !feof($handle)) {
                $chunk = fread($handle, $chunkSizeBytes);
                $status = $media->nextChunk($chunk);
            }

            fclose($handle);

            if ($status) {
                Log::info('Video uploaded successfully:', [
                    'user_id' => $user->id,
                    'video_id' => $status->getId(),
                    'title' => $videoData['title']
                ]);
                return $status;
            }

            throw new \Exception('Video upload failed - no response received');

        } catch (\Exception $e) {
            Log::error('YouTube video upload failed:', [
                'user_id' => $user->id,
                'title' => $videoData['title'] ?? 'Unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Upload thumbnail for video
     */
    public function uploadThumbnail(User $user, string $videoId, UploadedFile $thumbnail): bool
    {
        try {
            $youtube = $this->getYouTubeService($user);

            // Create thumbnail request
            $thumbnailRequest = new ThumbnailSetRequest();
            
            // Upload thumbnail
            $response = $youtube->thumbnails->set($videoId, $thumbnailRequest, [
                'data' => file_get_contents($thumbnail->getRealPath()),
                'mimeType' => $thumbnail->getMimeType(),
                'uploadType' => 'media'
            ]);

            Log::info('Thumbnail uploaded successfully:', [
                'user_id' => $user->id,
                'video_id' => $videoId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Thumbnail upload failed:', [
                'user_id' => $user->id,
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Add video to playlist
     */
    public function addVideoToPlaylist(User $user, string $videoId, string $playlistId): bool
    {
        try {
            $youtube = $this->getYouTubeService($user);

            // Create resource ID
            $resourceId = new ResourceId();
            $resourceId->setKind('youtube#video');
            $resourceId->setVideoId($videoId);

            // Create playlist item snippet
            $playlistItemSnippet = new PlaylistItemSnippet();
            $playlistItemSnippet->setPlaylistId($playlistId);
            $playlistItemSnippet->setResourceId($resourceId);

            // Create playlist item
            $playlistItem = new PlaylistItem();
            $playlistItem->setSnippet($playlistItemSnippet);

            // Insert playlist item
            $response = $youtube->playlistItems->insert('snippet', $playlistItem);

            Log::info('Video added to playlist successfully:', [
                'user_id' => $user->id,
                'video_id' => $videoId,
                'playlist_id' => $playlistId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to add video to playlist:', [
                'user_id' => $user->id,
                'video_id' => $videoId,
                'playlist_id' => $playlistId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get video details
     */
    public function getVideo(User $user, string $videoId): ?Video
    {
        try {
            $youtube = $this->getYouTubeService($user);
            
            $response = $youtube->videos->listVideos('snippet,status,statistics', [
                'id' => $videoId
            ]);

            $videos = $response->getItems();
            return !empty($videos) ? $videos[0] : null;

        } catch (\Exception $e) {
            Log::error('Failed to get video details:', [
                'user_id' => $user->id,
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get user's uploaded videos
     */
    public function getUserVideos(User $user, int $maxResults = 25): array
    {
        try {
            $youtube = $this->getYouTubeService($user);

            // First get the user's channel
            $channels = $youtube->channels->listChannels('contentDetails', [
                'mine' => true
            ]);

            if (empty($channels->getItems())) {
                return [];
            }

            $uploadsPlaylistId = $channels->getItems()[0]->getContentDetails()->getRelatedPlaylists()->getUploads();

            // Get videos from uploads playlist
            $playlistItems = $youtube->playlistItems->listPlaylistItems('snippet', [
                'playlistId' => $uploadsPlaylistId,
                'maxResults' => $maxResults
            ]);

            return $playlistItems->getItems() ?? [];

        } catch (\Exception $e) {
            Log::error('Failed to get user videos:', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Delete video
     */
    public function deleteVideo(User $user, string $videoId): bool
    {
        try {
            $youtube = $this->getYouTubeService($user);
            
            $youtube->videos->delete($videoId);

            Log::info('Video deleted successfully:', [
                'user_id' => $user->id,
                'video_id' => $videoId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to delete video:', [
                'user_id' => $user->id,
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Update video details
     */
    public function updateVideo(User $user, string $videoId, array $updateData): ?Video
    {
        try {
            $youtube = $this->getYouTubeService($user);

            // First get the current video
            $currentVideo = $this->getVideo($user, $videoId);
            if (!$currentVideo) {
                throw new \Exception('Video not found');
            }

            // Update snippet if provided
            if (isset($updateData['title']) || isset($updateData['description']) || isset($updateData['tags'])) {
                $snippet = $currentVideo->getSnippet();
                
                if (isset($updateData['title'])) {
                    $snippet->setTitle($updateData['title']);
                }
                
                if (isset($updateData['description'])) {
                    $snippet->setDescription($updateData['description']);
                }
                
                if (isset($updateData['tags'])) {
                    $tags = is_array($updateData['tags']) 
                        ? $updateData['tags'] 
                        : array_map('trim', explode(',', $updateData['tags']));
                    $snippet->setTags($tags);
                }
                
                $currentVideo->setSnippet($snippet);
            }

            // Update status if provided
            if (isset($updateData['privacy'])) {
                $status = $currentVideo->getStatus();
                $status->setPrivacyStatus($updateData['privacy']);
                $currentVideo->setStatus($status);
            }

            // Update video
            $response = $youtube->videos->update('snippet,status', $currentVideo);

            Log::info('Video updated successfully:', [
                'user_id' => $user->id,
                'video_id' => $videoId
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('Failed to update video:', [
                'user_id' => $user->id,
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get channel information
     */
    public function getChannelInfo(User $user): ?array
    {
        try {
            $youtube = $this->getYouTubeService($user);
            
            $channels = $youtube->channels->listChannels('snippet,statistics', [
                'mine' => true
            ]);

            if (empty($channels->getItems())) {
                return null;
            }

            $channel = $channels->getItems()[0];
            
            return [
                'id' => $channel->getId(),
                'title' => $channel->getSnippet()->getTitle(),
                'description' => $channel->getSnippet()->getDescription(),
                'thumbnail' => $channel->getSnippet()->getThumbnails()->getDefault()->getUrl(),
                'subscriber_count' => $channel->getStatistics()->getSubscriberCount(),
                'video_count' => $channel->getStatistics()->getVideoCount(),
                'view_count' => $channel->getStatistics()->getViewCount(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get channel info:', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}