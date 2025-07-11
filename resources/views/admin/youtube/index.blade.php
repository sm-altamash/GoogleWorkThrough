@extends('admin.layouts.master')
@section('title', 'YouTube Upload')

{{-- Add custom styles for better UX --}}
@push('styles')
    <style>
        .file-upload-wrapper {
            position: relative;
            overflow: hidden;
        }

        .file-upload-progress {
            display: none;
            margin-top: 10px;
        }

        .upload-preview {
            margin-top: 15px;
            padding: 10px;
            border: 1px dashed #dee2e6;
            border-radius: 8px;
            display: none;
        }

        .file-info {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .error-details {
            background-color: #f8d7da;
            border: 1px solid #f5c2c7;
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin-top: 0.5rem;
        }

        .size-limit-info {
            font-size: 0.75rem;
            color: #6c757d;
            margin-top: 0.25rem;
        }

        .connection-status {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .connection-connected {
            background-color: #d1e7dd;
            border: 1px solid #badbcc;
            color: #0f5132;
        }

        .connection-disconnected {
            background-color: #f8d7da;
            border: 1px solid #f5c2c7;
            color: #842029;
        }
    </style>
@endpush

@section('content')
    <section class="app-video-upload">
        {{-- Breadcrumb --}}
        <h4 class="py-3 mb-4">
            <span class="text-muted fw-light">YouTube /</span> Upload
        </h4>

        {{-- YouTube Connection Status --}}
        @php
            $isConnected = !empty($playlists) || session('success');
        @endphp

        <div class="connection-status {{ $isConnected ? 'connection-connected' : 'connection-disconnected' }}">
            @if($isConnected)
                <i class="ti ti-check-circle"></i>
                <span>YouTube account connected and ready for uploads</span>
            @else
                <i class="ti ti-alert-circle"></i>
                <span>YouTube account not connected. <a href="{{ route('youtube.auth') }}" class="text-decoration-none">Connect now</a></span>
            @endif
        </div>

        {{-- Main Upload Card --}}
        <div class="card shadow-sm border border-primary-subtle">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap">
                <h5 class="mb-0">
                    <i class="ti ti-brand-youtube text-danger me-2"></i>
                    Upload Video
                </h5>
                <div class="d-flex flex-column align-items-end">
                    <small class="text-muted">Supported: MP4, MOV, AVI, WMV, MPEG, WebM</small>
                    <small class="text-muted">Max size: 128MB video, 2MB thumbnail</small>
                </div>
            </div>

            <div class="card-body">
                {{-- Success Messages --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <i class="ti ti-check me-1"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Error Messages with Details --}}
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <div class="d-flex align-items-center mb-2">
                            <i class="ti ti-alert-circle me-1"></i>
                            <strong>Please fix the following errors:</strong>
                        </div>

                        {{-- Display detailed validation errors --}}
                        <div class="error-details">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                {{-- Upload Form with Enhanced Validation --}}
                <form id="uploadForm" action="{{ route('youtube.upload') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    @csrf

                    {{-- Progress Bar (Hidden by default) --}}
                    <div id="uploadProgress" class="file-upload-progress">
                        <div class="progress mb-3">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                        </div>
                        <div class="text-center">
                            <small class="text-muted">Uploading... Please don't close this page.</small>
                        </div>
                    </div>

                    <div class="row g-3">
                        {{-- Title Field with Character Counter --}}
                        <div class="col-12 col-md-6">
                            <label for="title" class="form-label fw-semibold">
                                Title <span class="text-danger">*</span>
                            </label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                class="form-control @error('title') is-invalid @enderror"
                                placeholder="Amazing Laravel Tutorial"
                                maxlength="100"
                                value="{{ old('title') }}"
                                required>

                            <div class="size-limit-info">
                                <span id="titleCounter">0</span>/100 characters
                            </div>

                            @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Privacy Setting --}}
                        <div class="col-12 col-md-3">
                            <label for="privacy" class="form-label fw-semibold">
                                Privacy <span class="text-danger">*</span>
                            </label>
                            <select id="privacy" name="privacy" class="form-select @error('privacy') is-invalid @enderror" required>
                                <option value="public" {{ old('privacy') == 'public' ? 'selected' : '' }}>
                                    🌍 Public
                                </option>
                                <option value="unlisted" {{ old('privacy') == 'unlisted' ? 'selected' : '' }}>
                                    🔗 Unlisted
                                </option>
                                <option value="private" {{ old('privacy') == 'private' ? 'selected' : '' }}>
                                    🔒 Private
                                </option>
                            </select>
                            @error('privacy')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Tags with Smart Input --}}
                        <div class="col-12 col-md-3">
                            <label for="tags" class="form-label fw-semibold">Tags</label>
                            <input
                                type="text"
                                id="tags"
                                name="tags"
                                class="form-control @error('tags') is-invalid @enderror"
                                placeholder="laravel,php,tutorial"
                                maxlength="500"
                                value="{{ old('tags') }}"
                                data-bs-toggle="tooltip"
                                title="Separate tags with commas. Each tag max 30 characters.">

                            <div class="size-limit-info">
                                Separate with commas, max 30 chars per tag
                            </div>

                            @error('tags')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Description with Character Counter --}}
                        <div class="col-12">
                            <label for="description" class="form-label fw-semibold">Description</label>
                            <textarea
                                id="description"
                                name="description"
                                class="form-control @error('description') is-invalid @enderror"
                                rows="4"
                                maxlength="5000"
                                placeholder="Write a detailed description of your video content...">{{ old('description') }}</textarea>

                            <div class="size-limit-info">
                                <span id="descCounter">0</span>/5000 characters
                            </div>

                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Thumbnail Upload (Now Required) --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="thumbnail">
                                Thumbnail <span class="text-danger">*</span>
                            </label>
                            <input
                                type="file"
                                class="form-control @error('thumbnail') is-invalid @enderror"
                                id="thumbnail"
                                name="thumbnail"
                                accept="image/jpeg,image/jpg,image/png"
                                required>

                            <div class="size-limit-info">
                                Required: JPEG/PNG, min 640x360px, max 2MB
                            </div>

                            {{-- Thumbnail Preview --}}
                            <div id="thumbnailPreview" class="upload-preview">
                                <img id="thumbnailImg" src="" alt="Thumbnail preview" style="max-width: 200px; max-height: 113px; border-radius: 4px;">
                                <div class="file-info mt-2">
                                    <span id="thumbnailInfo"></span>
                                </div>
                            </div>

                            @error('thumbnail')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Playlist Selector with Enhanced Options --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="playlist">Playlist</label>
                            <select id="playlist" name="playlist" class="form-select">
                                <option value="">— No Playlist —</option>

                                @if(count($playlists) > 0)
                                    <optgroup label="📋 Your Playlists">
                                        @foreach ($playlists as $plist)
                                            <option value="{{ $plist->getId() }}" {{ old('playlist') == $plist->getId() ? 'selected' : '' }}>
                                                {{ $plist->getSnippet()->getTitle() }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                    <option disabled>──────────</option>
                                @endif

                                <option value="NEW:" {{ old('playlist') == 'NEW:' ? 'selected' : '' }}>
                                    ➕ Create New Playlist...
                                </option>
                            </select>

                            {{-- New Playlist Name Input (Hidden by default) --}}
                            <div id="newPlaylistDiv" style="display: none; margin-top: 10px;">
                                <input
                                    type="text"
                                    id="newPlaylistName"
                                    class="form-control"
                                    placeholder="Enter new playlist name"
                                    maxlength="150">
                                <div class="size-limit-info">
                                    New playlist will be created as private
                                </div>
                            </div>
                        </div>

                        {{-- Video File Upload with Enhanced Validation --}}
                        <div class="col-12">
                            <label for="video" class="form-label fw-semibold">
                                Video File <span class="text-danger">*</span>
                            </label>
                            <input
                                class="form-control @error('video') is-invalid @enderror"
                                type="file"
                                id="video"
                                name="video"
                                accept="video/mp4,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/mpeg,video/webm"
                                required>

                            <div class="size-limit-info">
                                Supported formats: MP4, MOV, AVI, WMV, MPEG, WebM (max 128MB)
                            </div>

                            {{-- Video Preview --}}
                            <div id="videoPreview" class="upload-preview">
                                <div class="file-info">
                                    <span id="videoInfo"></span>
                                </div>
                            </div>

                            @error('video')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Submit Button with Loading State --}}
                        <div class="col-12 text-end">
                            <button type="submit" id="submitBtn" class="btn btn-primary btn-lg" {{ !$isConnected ? 'disabled' : '' }}>
                                <span class="upload-text">
                                    <i class="ti ti-upload me-1"></i> Upload to YouTube
                                </span>
                                <span class="uploading-text d-none">
                                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    Uploading...
                                </span>
                            </button>

                            @if(!$isConnected)
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <a href="{{ route('youtube.auth') }}" class="text-decoration-none">
                                            Connect YouTube account to enable uploads
                                        </a>
                                    </small>
                                </div>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection

{{-- Enhanced JavaScript for Better UX --}}
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // File size constants (in bytes) - matching controller
            const MAX_VIDEO_SIZE = 128 * 1024 * 1024; // 128MB
            const MAX_THUMBNAIL_SIZE = 2 * 1024 * 1024; // 2MB

            // Allowed file types - matching controller
            const ALLOWED_VIDEO_TYPES = [
                'video/mp4', 'video/quicktime', 'video/x-msvideo',
                'video/x-ms-wmv', 'video/mpeg', 'video/webm'
            ];

            const ALLOWED_THUMBNAIL_TYPES = [
                'image/jpeg', 'image/jpg', 'image/png'
            ];

            // Character counters
            const titleInput = document.getElementById('title');
            const titleCounter = document.getElementById('titleCounter');
            const descInput = document.getElementById('description');
            const descCounter = document.getElementById('descCounter');

            // Update character counters
            function updateCounter(input, counter) {
                if (input && counter) {
                    counter.textContent = input.value.length;

                    // Color coding for limits
                    const maxLength = input.getAttribute('maxlength');
                    const percentage = (input.value.length / maxLength) * 100;

                    if (percentage > 90) {
                        counter.style.color = '#dc3545'; // Red
                    } else if (percentage > 75) {
                        counter.style.color = '#fd7e14'; // Orange
                    } else {
                        counter.style.color = '#6c757d'; // Gray
                    }
                }
            }

            // Initialize counters
            updateCounter(titleInput, titleCounter);
            updateCounter(descInput, descCounter);

            // Add event listeners for live counting
            titleInput?.addEventListener('input', () => updateCounter(titleInput, titleCounter));
            descInput?.addEventListener('input', () => updateCounter(descInput, descCounter));

            // Playlist selector logic
            const playlistSelect = document.getElementById('playlist');
            const newPlaylistDiv = document.getElementById('newPlaylistDiv');
            const newPlaylistInput = document.getElementById('newPlaylistName');

            playlistSelect?.addEventListener('change', function() {
                if (this.value === 'NEW:') {
                    newPlaylistDiv.style.display = 'block';
                    newPlaylistInput.required = true;
                    newPlaylistInput.focus();
                } else {
                    newPlaylistDiv.style.display = 'none';
                    newPlaylistInput.required = false;
                }
            });

            // Update playlist value when typing new name
            newPlaylistInput?.addEventListener('input', function() {
                if (playlistSelect.value === 'NEW:' && this.value.trim()) {
                    playlistSelect.value = 'NEW:' + this.value.trim();
                }
            });

            // File validation functions
            function validateFileSize(file, maxSize, fieldName) {
                if (file.size > maxSize) {
                    const maxSizeMB = (maxSize / (1024 * 1024)).toFixed(1);
                    const fileSizeMB = (file.size / (1024 * 1024)).toFixed(1);
                    alert(`${fieldName} file size (${fileSizeMB}MB) exceeds the maximum allowed size of ${maxSizeMB}MB.`);
                    return false;
                }
                return true;
            }

            function validateFileType(file, allowedTypes, fieldName) {
                if (!allowedTypes.includes(file.type)) {
                    alert(`${fieldName} file type "${file.type}" is not supported.`);
                    return false;
                }
                return true;
            }

            // Thumbnail preview and validation
            const thumbnailInput = document.getElementById('thumbnail');
            const thumbnailPreview = document.getElementById('thumbnailPreview');
            const thumbnailImg = document.getElementById('thumbnailImg');
            const thumbnailInfo = document.getElementById('thumbnailInfo');

            thumbnailInput?.addEventListener('change', function() {
                const file = this.files[0];
                thumbnailPreview.style.display = 'none';

                if (file) {
                    // Validate file type and size
                    if (!validateFileType(file, ALLOWED_THUMBNAIL_TYPES, 'Thumbnail') ||
                        !validateFileSize(file, MAX_THUMBNAIL_SIZE, 'Thumbnail')) {
                        this.value = '';
                        return;
                    }

                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        thumbnailImg.src = e.target.result;
                        thumbnailInfo.textContent = `${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
                        thumbnailPreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Video file validation and preview
            const videoInput = document.getElementById('video');
            const videoPreview = document.getElementById('videoPreview');
            const videoInfo = document.getElementById('videoInfo');

            videoInput?.addEventListener('change', function() {
                const file = this.files[0];
                videoPreview.style.display = 'none';

                if (file) {
                    // Validate file type and size
                    if (!validateFileType(file, ALLOWED_VIDEO_TYPES, 'Video') ||
                        !validateFileSize(file, MAX_VIDEO_SIZE, 'Video')) {
                        this.value = '';
                        return;
                    }

                    // Show file info
                    const fileSizeMB = (file.size / (1024 * 1024)).toFixed(1);
                    videoInfo.innerHTML = `
                <strong>${file.name}</strong><br>
                Size: ${fileSizeMB} MB | Type: ${file.type}
            `;
                    videoPreview.style.display = 'block';
                }
            });

            // Form submission handling
            const uploadForm = document.getElementById('uploadForm');
            const submitBtn = document.getElementById('submitBtn');
            const uploadProgress = document.getElementById('uploadProgress');

            uploadForm?.addEventListener('submit', function(e) {
                // Validate required files
                const videoFile = videoInput.files[0];
                const thumbnailFile = thumbnailInput.files[0];

                if (!videoFile) {
                    alert('Please select a video file to upload.');
                    e.preventDefault();
                    return;
                }

                if (!thumbnailFile) {
                    alert('Please select a thumbnail image. This is required for all uploads.');
                    e.preventDefault();
                    return;
                }

                // Show loading state
                submitBtn.disabled = true;
                submitBtn.querySelector('.upload-text').classList.add('d-none');
                submitBtn.querySelector('.uploading-text').classList.remove('d-none');

                // Show progress bar
                uploadProgress.style.display = 'block';

                // Simulate progress (since we can't track real upload progress easily)
                let progress = 0;
                const progressBar = uploadProgress.querySelector('.progress-bar');
                const interval = setInterval(() => {
                    progress += Math.random() * 15;
                    if (progress > 90) progress = 90; // Don't complete until real completion
                    progressBar.style.width = progress + '%';
                }, 1000);

                // Clean up interval if form submission fails
                setTimeout(() => {
                    clearInterval(interval);
                }, 30000); // Stop after 30 seconds
            });

            // Initialize tooltips if Bootstrap tooltips are available
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }

            // Auto-resize textareas
            const textareas = document.querySelectorAll('textarea');
            textareas.forEach(textarea => {
                textarea.addEventListener('input', function() {
                    this.style.height = 'auto';
                    this.style.height = (this.scrollHeight) + 'px';
                });
            });
        });
    </script>
@endpush
