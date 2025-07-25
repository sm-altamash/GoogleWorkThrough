<?php

namespace App\Http\Controllers;

use App\Services\YouTubeService;
use App\Services\GoogleClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class YouTubeController extends Controller
{
    protected YouTubeService $youtubeService;
    protected GoogleClientService $googleClientService;

    public function __construct(YouTubeService $youtubeService, GoogleClientService $googleClientService)
    {
        $this->youtubeService = $youtubeService;
        $this->googleClientService = $googleClientService;
    }

    /**
     * Show YouTube upload form
     */
    public function index()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login');
            }

            $playlists = [];
            $isConnected = false;

            // Check if user has valid YouTube connection
            if ($this->youtubeService->hasValidConnection($user)) {
                $playlists = $this->youtubeService->getUserPlaylists($user);
                $isConnected = true;
                session(['google_connected' => true]);
            } else {
                session(['google_connected' => false]);
            }

            Log::info('YouTube upload page accessed:', [
                'user_id' => $user->id,
                'connected' => $isConnected,
                'playlists_count' => count($playlists)
            ]);

            return view('admin.youtube.index', compact('playlists', 'isConnected'));

        } catch (\Exception $e) {
            Log::error('Error loading YouTube upload page:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to load YouTube upload page');
        }
    }

    /**
     * Redirect to Google OAuth for YouTube
     */
    public function auth()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login')->with('error', 'Please log in first');
            }

            $authUrl = $this->googleClientService->getAuthUrl();
            
            Log::info('YouTube auth redirect:', [
                'user_id' => $user->id,
                'url' => $authUrl
            ]);
            
            return redirect($authUrl);
            
        } catch (\Exception $e) {
            Log::error('Error generating YouTube auth URL:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->with('error', 'Failed to connect to YouTube. Please try again.');
        }
    }

    /**
     * Upload video to YouTube
     */
    public function upload(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login');
            }

            // Check YouTube connection
            if (!$this->youtubeService->hasValidConnection($user)) {
                return redirect()->back()->with('error', 'YouTube account not connected. Please connect your account first.');
            }

            // Validate request
            $validator = $this->validateUploadRequest($request);
            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $validated = $validator->validated();

            Log::info('YouTube upload started:', [
                'user_id' => $user->id,
                'title' => $validated['title'],
                'privacy' => $validated['privacy']
            ]);

            // Handle playlist creation/selection
            $playlistId = null;
            if (!empty($validated['playlist'])) {
                if (strpos($validated['playlist'], 'NEW:') === 0) {
                    // Create new playlist
                    $playlistName = substr($validated['playlist'], 4);
                    if (!empty($playlistName)) {
                        $playlist = $this->youtubeService->createPlaylist($user, $playlistName);
                        $playlistId = $playlist ? $playlist->getId() : null;
                    }
                } else {
                    // Use existing playlist
                    $playlistId = $validated['playlist'];
                }
            }

            // Prepare video data
            $videoData = [
                'title' => $validated['title'],
                'description' => $validated['description'] ?? '',
                'privacy' => $validated['privacy'],
                'tags' => $validated['tags'] ?? '',
                'video_file' => $validated['video']
            ];

            // Upload video
            $video = $this->youtubeService->uploadVideo($user, $videoData);
            
            if (!$video) {
                return redirect()->back()
                    ->with('error', 'Failed to upload video to YouTube. Please try again.')
                    ->withInput();
            }

            $videoId = $video->getId();

            // Upload thumbnail
            $thumbnailUploaded = false;
            if ($request->hasFile('thumbnail')) {
                $thumbnailUploaded = $this->youtubeService->uploadThumbnail($user, $videoId, $validated['thumbnail']);
            }

            // Add to playlist if specified
            if ($playlistId) {
                $this->youtubeService->addVideoToPlaylist($user, $videoId, $playlistId);
            }

            Log::info('YouTube upload completed:', [
                'user_id' => $user->id,
                'video_id' => $videoId,
                'thumbnail_uploaded' => $thumbnailUploaded,
                'playlist_id' => $playlistId
            ]);

            $successMessage = "Video uploaded successfully to YouTube!";
            if ($thumbnailUploaded) {
                $successMessage .= " Thumbnail uploaded.";
            }
            if ($playlistId) {
                $successMessage .= " Added to playlist.";
            }

            return redirect()->route('youtube.index')->with('success', $successMessage);

        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('YouTube upload error:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Upload failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Validate upload request
     */
    protected function validateUploadRequest(Request $request)
    {
        $rules = [
            'title' => 'required|string|max:100',
            'description' => 'nullable|string|max:5000',
            'privacy' => 'required|in:public,unlisted,private',
            'tags' => 'nullable|string|max:500',
            'playlist' => 'nullable|string',
            'video' => 'required|file|mimes:mp4,mov,avi,wmv,mpeg,webm|max:131072', // 128MB
            'thumbnail' => 'required|file|mimes:jpeg,jpg,png|max:2048|dimensions:min_width=640,min_height=360'
        ];

        $messages = [
            'title.required' => 'Video title is required.',
            'title.max' => 'Video title cannot exceed 100 characters.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'privacy.required' => 'Privacy setting is required.',
            'privacy.in' => 'Privacy setting must be public, unlisted, or private.',
            'tags.max' => 'Tags cannot exceed 500 characters.',
            'video.required' => 'Video file is required.',
            'video.file' => 'Video must be a valid file.',
            'video.mimes' => 'Video must be a file of type: mp4, mov, avi, wmv, mpeg, webm.',
            'video.max' => 'Video file cannot exceed 128MB.',
            'thumbnail.required' => 'Thumbnail image is required.',
            'thumbnail.file' => 'Thumbnail must be a valid file.',
            'thumbnail.mimes' => 'Thumbnail must be a file of type: jpeg, jpg, png.',
            'thumbnail.max' => 'Thumbnail file cannot exceed 2MB.',
            'thumbnail.dimensions' => 'Thumbnail must be at least 640x360 pixels.'
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Show user's uploaded videos
     */
    public function videos()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login');
            }

            if (!$this->youtubeService->hasValidConnection($user)) {
                return redirect()->route('youtube.index')->with('error', 'YouTube account not connected.');
            }

            $videos = $this->youtubeService->getUserVideos($user, 50);
            $channelInfo = $this->youtubeService->getChannelInfo($user);

            return view('admin.youtube.index', compact('videos', 'channelInfo'));

        } catch (\Exception $e) {
            Log::error('Error loading YouTube videos:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to load YouTube videos');
        }
    }

    /**
     * Show video details
     */
    public function show(string $videoId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login');
            }

            if (!$this->youtubeService->hasValidConnection($user)) {
                return redirect()->route('youtube.index')->with('error', 'YouTube account not connected.');
            }

            $video = $this->youtubeService->getVideo($user, $videoId);
            
            if (!$video) {
                return redirect()->route('youtube.videos')->with('error', 'Video not found.');
            }

            return view('admin.youtube.index', compact('video'));

        } catch (\Exception $e) {
            Log::error('Error loading video details:', [
                'user_id' => Auth::id(),
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to load video details');
        }
    }

    /**
     * Update video details
     */
    public function update(Request $request, string $videoId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login');
            }

            if (!$this->youtubeService->hasValidConnection($user)) {
                return redirect()->route('youtube.index')->with('error', 'YouTube account not connected.');
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:100',
                'description' => 'nullable|string|max:5000',
                'privacy' => 'required|in:public,unlisted,private',
                'tags' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $updateData = $validator->validated();
            $video = $this->youtubeService->updateVideo($user, $videoId, $updateData);

            if (!$video) {
                return redirect()->back()->with('error', 'Failed to update video.');
            }

            Log::info('Video updated successfully:', [
                'user_id' => $user->id,
                'video_id' => $videoId
            ]);

            return redirect()->route('youtube.show', $videoId)->with('success', 'Video updated successfully!');

        } catch (\Exception $e) {
            Log::error('Error updating video:', [
                'user_id' => Auth::id(),
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to update video: ' . $e->getMessage());
        }
    }

    /**
     * Delete video
     */
    public function destroy(string $videoId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login');
            }

            if (!$this->youtubeService->hasValidConnection($user)) {
                return redirect()->route('youtube.index')->with('error', 'YouTube account not connected.');
            }

            $success = $this->youtubeService->deleteVideo($user, $videoId);

            if (!$success) {
                return redirect()->back()->with('error', 'Failed to delete video.');
            }

            Log::info('Video deleted successfully:', [
                'user_id' => $user->id,
                'video_id' => $videoId
            ]);

            return redirect()->route('youtube.videos')->with('success', 'Video deleted successfully!');

        } catch (\Exception $e) {
            Log::error('Error deleting video:', [
                'user_id' => Auth::id(),
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to delete video: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect YouTube account
     */
    public function disconnect()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login');
            }

            // Clear Google token (this will disconnect YouTube as well)
            if ($user->googleToken) {
                $this->googleClientService->clearToken($user);
            }

            session(['google_connected' => false]);

            Log::info('YouTube account disconnected:', [
                'user_id' => $user->id
            ]);

            return redirect()->route('youtube.index')->with('success', 'YouTube account disconnected successfully!');

        } catch (\Exception $e) {
            Log::error('Error disconnecting YouTube account:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to disconnect YouTube account.');
        }
    }

    /**
     * Get channel statistics for dashboard
     */
    public function dashboard()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login');
            }

            if (!$this->youtubeService->hasValidConnection($user)) {
                return redirect()->route('youtube.index')->with('error', 'YouTube account not connected.');
            }

            $channelInfo = $this->youtubeService->getChannelInfo($user);
            $recentVideos = $this->youtubeService->getUserVideos($user, 10);

            return view('admin.youtube.index', compact('channelInfo', 'recentVideos'));

        } catch (\Exception $e) {
            Log::error('Error loading YouTube dashboard:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to load YouTube dashboard');
        }
    }

    /**
     * Get user playlists via AJAX
     */
    public function getPlaylists()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            if (!$this->youtubeService->hasValidConnection($user)) {
                return response()->json(['error' => 'YouTube account not connected'], 400);
            }

            $playlists = $this->youtubeService->getUserPlaylists($user);

            return response()->json([
                'success' => true,
                'playlists' => array_map(function($playlist) {
                    return [
                        'id' => $playlist->getId(),
                        'title' => $playlist->getSnippet()->getTitle(),
                        'description' => $playlist->getSnippet()->getDescription(),
                        'privacy' => $playlist->getStatus()->getPrivacyStatus()
                    ];
                }, $playlists)
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching playlists:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to fetch playlists'], 500);
        }
    }

    /**
     * Create playlist via AJAX
     */
    public function createPlaylist(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            if (!$this->youtubeService->hasValidConnection($user)) {
                return response()->json(['error' => 'YouTube account not connected'], 400);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:150',
                'description' => 'nullable|string|max:5000',
                'privacy' => 'required|in:public,unlisted,private'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 400);
            }

            $playlist = $this->youtubeService->createPlaylist(
                $user,
                $request->input('title'),
                $request->input('description', ''),
                $request->input('privacy', 'private')
            );

            if (!$playlist) {
                return response()->json(['error' => 'Failed to create playlist'], 500);
            }

            return response()->json([
                'success' => true,
                'playlist' => [
                    'id' => $playlist->getId(),
                    'title' => $playlist->getSnippet()->getTitle(),
                    'description' => $playlist->getSnippet()->getDescription(),
                    'privacy' => $playlist->getStatus()->getPrivacyStatus()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating playlist:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to create playlist'], 500);
        }
    }

    /**
     * Get video analytics
     */
    public function analytics(string $videoId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login');
            }

            if (!$this->youtubeService->hasValidConnection($user)) {
                return redirect()->route('youtube.index')->with('error', 'YouTube account not connected.');
            }

            $video = $this->youtubeService->getVideo($user, $videoId);
            
            if (!$video) {
                return redirect()->route('youtube.videos')->with('error', 'Video not found.');
            }

            // Get video statistics
            $statistics = $video->getStatistics();
            $analytics = [
                'views' => $statistics->getViewCount() ?? 0,
                'likes' => $statistics->getLikeCount() ?? 0,
                'comments' => $statistics->getCommentCount() ?? 0,
                'favorites' => $statistics->getFavoriteCount() ?? 0
            ];

            return view('admin.youtube.index', compact('video', 'analytics'));

        } catch (\Exception $e) {
            Log::error('Error loading video analytics:', [
                'user_id' => Auth::id(),
                'video_id' => $videoId,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to load video analytics');
        }
    }

    /**
     * Bulk operations on videos
     */
    public function bulkAction(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            if (!$this->youtubeService->hasValidConnection($user)) {
                return response()->json(['error' => 'YouTube account not connected'], 400);
            }

            $validator = Validator::make($request->all(), [
                'action' => 'required|in:delete,update_privacy,add_to_playlist',
                'video_ids' => 'required|array|min:1',
                'video_ids.*' => 'required|string',
                'privacy' => 'required_if:action,update_privacy|in:public,unlisted,private',
                'playlist_id' => 'required_if:action,add_to_playlist|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 400);
            }

            $action = $request->input('action');
            $videoIds = $request->input('video_ids');
            $successCount = 0;
            $errors = [];

            foreach ($videoIds as $videoId) {
                try {
                    switch ($action) {
                        case 'delete':
                            if ($this->youtubeService->deleteVideo($user, $videoId)) {
                                $successCount++;
                            } else {
                                $errors[] = "Failed to delete video: {$videoId}";
                            }
                            break;

                        case 'update_privacy':
                            $privacy = $request->input('privacy');
                            if ($this->youtubeService->updateVideo($user, $videoId, ['privacy' => $privacy])) {
                                $successCount++;
                            } else {
                                $errors[] = "Failed to update privacy for video: {$videoId}";
                            }
                            break;

                        case 'add_to_playlist':
                            $playlistId = $request->input('playlist_id');
                            if ($this->youtubeService->addVideoToPlaylist($user, $videoId, $playlistId)) {
                                $successCount++;
                            } else {
                                $errors[] = "Failed to add video to playlist: {$videoId}";
                            }
                            break;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error processing video {$videoId}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'processed' => $successCount,
                'total' => count($videoIds),
                'errors' => $errors
            ]);

        } catch (\Exception $e) {
            Log::error('Error in bulk action:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Bulk action failed'], 500);
        }
    }
}