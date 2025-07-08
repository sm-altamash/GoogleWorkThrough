@extends('admin.layouts.master')
@section('title', 'Google Calendar')
@section('content')
    <section>
        <h4 class="py-3 mb-4">
            <span class="text-muted fw-light">
                Google /
            </span> 
            Google Calendar
        </h4>

        @php
            $isConnected = session('google_connected', false);
            $connectionMessage = session('connection_message', '');
        @endphp
        <div class="card mb-5">
            <div class="card-header">
                <h5 class="mb-0">Connection Status</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="d-flex align-items-center" id="connection-status">
                        <i 
                        class="fas fa-plug" 
                        style="color: {{ $isConnected ? 'green' : 'red' }};"
                        ></i>
                        <span class="ms-2">
                        @if($isConnected)
                            Google Calendar Connected
                        @else
                            Google Calendar Not Connected
                        @endif
                        </span>
                    </span>

                    @if(!$isConnected)
                        <a href="http://localhost/excel/public/auth/google" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                            <i class="ti ti-brand-google"></i>
                            Connect Google Account
                        </a>
                    @endif
                </div>

                @if($connectionMessage)
                    <div class="alert {{ $isConnected ? 'alert-success' : 'alert-danger' }} mt-3">
                        {{ $connectionMessage ?: ($isConnected ? 'Ready to sync events and create new ones' : 'Connect your Google account to manage calendar events') }}
                    </div>
                @endif

                @if($isConnected)
                    <div class="d-flex gap-3 mt-3">
                        <button class="btn btn-outline-primary btn-sm" onclick="refreshEvents()">
                            <i class="ti ti-refresh"></i> Refresh Events
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="disconnectGoogle()">
                            <i class="ti ti-unlink"></i> Disconnect
                        </button>
                    </div>
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
                <i class="ti ti-check me-2"></i>
                <strong>Success!</strong> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible" role="alert">
                <i class="ti ti-alert-circle me-2"></i>
                <strong>Error!</strong> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible" role="alert">
                <i class="ti ti-alert-circle me-2"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
  

        <!-- Create Event Section (Only show if connected)  -->
        @if($isConnected)
            <div class="card shadow-sm border-primary-subtle mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">
                        <i class="ti ti-calendar-plus text-primary me-2"></i>
                        Create New Event
                    </h5>
                    <small class="text-muted">All times in {{ config('app.timezone', 'UTC') }}</small>
                </div>
                
                <div class="card-body">
                    <form id="createEventForm" action="{{ route('calendar.store') }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        
                        <div class="row g-3">
                            {{-- Event Title --}}
                            <div class="col-12 col-md-6">
                                <label for="event_title" class="form-label">
                                    Event Title <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="event_title" 
                                    name="title" 
                                    class="form-control @error('title') is-invalid @enderror" 
                                    placeholder="Enter event title"
                                    value="{{ old('title') }}"
                                    maxlength="100"
                                    required>
                                
                                <div class="timezone-info">
                                    <span id="titleCounter">0</span>/100 characters
                                </div>
                                
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Timezone --}}
                            <div class="col-12 col-md-6">
                                <label for="timezone" class="form-label">Timezone</label>
                                <select id="timezone" name="timezone" class="form-select @error('timezone') is-invalid @enderror">
                                    <option value="UTC" {{ old('timezone') == 'UTC' ? 'selected' : '' }}>🌍 UTC</option>
                                    <option value="Asia/Karachi" {{ old('timezone', 'Asia/Karachi') == 'Asia/Karachi' ? 'selected' : '' }}>🇵🇰 Asia/Karachi</option>
                                    <option value="America/New_York" {{ old('timezone') == 'America/New_York' ? 'selected' : '' }}>🇺🇸 America/New_York</option>
                                    <option value="Europe/London" {{ old('timezone') == 'Europe/London' ? 'selected' : '' }}>🇬🇧 Europe/London</option>
                                    <option value="Asia/Dubai" {{ old('timezone') == 'Asia/Dubai' ? 'selected' : '' }}>🇦🇪 Asia/Dubai</option>
                                    <option value="Asia/Tokyo" {{ old('timezone') == 'Asia/Tokyo' ? 'selected' : '' }}>🇯🇵 Asia/Tokyo</option>
                                </select>
                                
                                @error('timezone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Start Time --}}
                            <div class="col-12 col-md-6">
                                <label for="start_time" class="form-label">
                                    Start Time <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="datetime-local" 
                                    id="start_time" 
                                    name="start_time" 
                                    class="form-control @error('start_time') is-invalid @enderror"
                                    value="{{ old('start_time') }}"
                                    required>
                                
                                @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- End Time --}}
                            <div class="col-12 col-md-6">
                                <label for="end_time" class="form-label">
                                    End Time <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="datetime-local" 
                                    id="end_time" 
                                    name="end_time" 
                                    class="form-control @error('end_time') is-invalid @enderror"
                                    value="{{ old('end_time') }}"
                                    required>
                                
                                @error('end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Description --}}
                            <div class="col-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea 
                                    id="description" 
                                    name="description" 
                                    class="form-control @error('description') is-invalid @enderror" 
                                    rows="3"
                                    placeholder="Enter event description (optional)"
                                    maxlength="2000">{{ old('description') }}</textarea>
                                
                                <div class="timezone-info">
                                    <span id="descCounter">0</span>/2000 characters
                                </div>
                                
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Submit Button --}}
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary btn-lg" id="createEventBtn">
                                    <span class="create-text">
                                        <i class="ti ti-calendar-plus me-1"></i> Create Event
                                    </span>
                                    <span class="creating-text d-none">
                                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                        Creating...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif


        <div class="card mb-5">
            <div class="card-header">
                <h5 class="mb-0">
                <i class="ti ti-calendar-event text-primary me-2"></i>
                Your Calendar Events
                </h5>
            </div>

            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="d-flex align-items-center">
                        <i class="ti ti-calendar {{ $isConnected ? 'text-success' : 'text-danger' }}"></i>
                        <span class="ms-2">
                        @if($isConnected)
                            Events loaded from Google Calendar
                        @else
                            No Calendar Connection
                        @endif
                        </span>
                    </span>

                    @if($isConnected)
                        <button class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1" onclick="loadEvents()">
                            <i class="ti ti-refresh"></i> Refresh
                        </button>
                    @endif
                </div>

                <div id="events-container">
                    @if($isConnected)
                        <div class="loading-spinner text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span class="ms-2 text-muted">Loading your calendar events...</span>
                        </div>
                    @else
                        <div class="text-center text-muted">
                            <i class="ti ti-calendar-off fs-2 mb-2"></i>
                            <h6>No Calendar Connection</h6>
                            <p>Connect your Google account to view and manage your calendar events.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </section>
@endsection
@section('script')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeCalendar();
            
            setupCharacterCounters();
            
            setupFormValidation();
            
            @if($isConnected)
                loadEvents();
            @endif
        });

        function initializeCalendar() {
            // Set default datetime values
            const now = new Date();
            const oneHourLater = new Date(now.getTime() + 60 * 60 * 1000);
            
            const startInput = document.getElementById('start_time');
            const endInput = document.getElementById('end_time');
            
            if (startInput && !startInput.value) {
                startInput.value = formatDateTimeLocal(now);
            }
            
            if (endInput && !endInput.value) {
                endInput.value = formatDateTimeLocal(oneHourLater);
            }
            
            // Auto-update end time when start time changes
            if (startInput && endInput) {
                startInput.addEventListener('change', function() {
                    const startTime = new Date(this.value);
                    const endTime = new Date(startTime.getTime() + 60 * 60 * 1000);
                    endInput.value = formatDateTimeLocal(endTime);
                });
            }
        }

        function setupCharacterCounters() {
            const titleInput = document.getElementById('event_title');
            const titleCounter = document.getElementById('titleCounter');
            const descInput = document.getElementById('description');
            const descCounter = document.getElementById('descCounter');

            function updateCounter(input, counter) {
                if (input && counter) {
                    const length = input.value.length;
                    const maxLength = input.getAttribute('maxlength');
                    counter.textContent = length;
                    
                    // Color coding
                    const percentage = (length / maxLength) * 100;
                    counter.style.color = percentage > 90 ? '#dc3545' : 
                                         percentage > 75 ? '#fd7e14' : '#6c757d';
                }
            }

            // Initialize counters
            updateCounter(titleInput, titleCounter);
            updateCounter(descInput, descCounter);

            // Add event listeners
            titleInput?.addEventListener('input', () => updateCounter(titleInput, titleCounter));
            descInput?.addEventListener('input', () => updateCounter(descInput, descCounter));
        }

        function setupFormValidation() {
            const form = document.getElementById('createEventForm');
            const submitBtn = document.getElementById('createEventBtn');
            
            form?.addEventListener('submit', function(e) {
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                form.classList.add('was-validated');
                
                if (form.checkValidity()) {
                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.querySelector('.create-text').classList.add('d-none');
                    submitBtn.querySelector('.creating-text').classList.remove('d-none');
                }
            });
        }

        function formatDateTimeLocal(date) {
            const offset = date.getTimezoneOffset() * 60000;
            const localDate = new Date(date.getTime() - offset);
            return localDate.toISOString().slice(0, 16);
        }

        async function loadEvents() {
            const container = document.getElementById('events-container');
            const loadingIndicator = document.getElementById('loading-indicator');
            
            // Show loading
            loadingIndicator.classList.remove('d-none');
            container.innerHTML = `
                <div class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span>Loading your calendar events...</span>
                </div>
            `;

            try {
                const response = await fetch('{{ route("calendar.index") }}', {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    displayEvents(data.events);
                } else {
                    container.innerHTML = `
                        <div class="no-events">
                            <i class="ti ti-alert-circle"></i>
                            <h6>Failed to Load Events</h6>
                            <p>${data.message || 'Unable to retrieve calendar events.'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading events:', error);
                container.innerHTML = `
                    <div class="no-events">
                        <i class="ti ti-wifi-off"></i>
                        <h6>Connection Error</h6>
                        <p>Unable to connect to Google Calendar. Please try again.</p>
                    </div>
                `;
            } finally {
                loadingIndicator.classList.add('d-none');
            }
        }

        function displayEvents(events) {
            const container = document.getElementById('events-container');
            
            if (!events || events.length === 0) {
                container.innerHTML = `
                    <div class="no-events">
                        <i class="ti ti-calendar-off"></i>
                        <h6>No Events Found</h6>
                        <p>You don't have any upcoming events. Create one above!</p>
                    </div>
                `;
                return;
            }

            let html = '';
            events.forEach(event => {
                const startTime = formatEventDateTime(event.start?.dateTime || event.start?.date);
                const endTime = formatEventDateTime(event.end?.dateTime || event.end?.date);
                
                html += `
                    <div class="calendar-event">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="event-title">${escapeHtml(event.summary || 'Untitled Event')}</div>
                                <div class="event-time mb-2">
                                    <i class="ti ti-clock me-1"></i>
                                    ${startTime} - ${endTime}
                                </div>
                                ${event.description ? `<div class="event-description">${escapeHtml(event.description)}</div>` : ''}
                            </div>
                            <div class="event-actions">
                                ${event.htmlLink ? `
                                    <a href="${event.htmlLink}" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="ti ti-external-link"></i> Open
                                    </a>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function formatEventDateTime(dateTimeString) {
            if (!dateTimeString) return 'No time';
            
            try {
                const date = new Date(dateTimeString);
                return date.toLocaleString('en-US', {
                    weekday: 'short',
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (error) {
                return dateTimeString;
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function refreshEvents() {
            await loadEvents();
            showNotification('Events refreshed successfully!', 'success');
        }

        function showNotification(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="ti ti-${type === 'success' ? 'check' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.querySelector('.app-calendar-integration').insertBefore(
                alertDiv, 
                document.querySelector('.app-calendar-integration').firstChild
            );
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Auto-refresh events every 5 minutes if connected
        @if($isConnected)
            setInterval(function() {
                loadEvents();
            }, 5 * 60 * 1000);
        @endif
    </script>

@endsection