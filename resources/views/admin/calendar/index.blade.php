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
                        <a href="{{ route('google.auth') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
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
                        <span id="form-title">Create New Event</span>
                    </h5>
                    <small class="text-muted">All times in {{ config('app.timezone', 'UTC') }}</small>
                </div>
                
                <div class="card-body">
                    <form id="eventForm" action="{{ route('calendar.store') }}" method="POST" class="needs-validation" novalidate>
                        @csrf
                        <input type="hidden" id="form-method" name="_method" value="POST">
                        <input type="hidden" id="event-id" name="event_id" value="">
                        
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
                            <div class="col-12 d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" id="cancelBtn" onclick="resetForm()" style="display: none;">
                                    <i class="ti ti-x me-1"></i> Cancel
                                </button>
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <span class="submit-text">
                                        <i class="ti ti-calendar-plus me-1"></i> Create Event
                                    </span>
                                    <span class="submitting-text d-none">
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

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">
                            <i class="ti ti-trash text-danger me-2"></i>
                            Delete Event
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this event?</p>
                        <div class="alert alert-warning">
                            <i class="ti ti-alert-triangle me-2"></i>
                            <strong id="delete-event-title"></strong>
                        </div>
                        <p class="text-muted small">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                            <i class="ti ti-trash me-1"></i> Delete Event
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </section>

    <style>
        .calendar-event {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .calendar-event:hover {
            border-color: #007bff;
            box-shadow: 0 2px 8px rgba(0,123,255,0.1);
        }

        .event-title {
            font-weight: 600;
            color: #212529;
            margin-bottom: 8px;
        }

        .event-time {
            color: #6c757d;
            font-size: 0.9em;
        }

        .event-description {
            color: #495057;
            font-size: 0.9em;
            margin-top: 8px;
        }

        .event-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .no-events {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }

        .no-events i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .loading-spinner {
            text-align: center;
            padding: 40px;
        }

        .timezone-info {
            font-size: 0.8em;
            color: #6c757d;
            margin-top: 4px;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .event-card {
            border-left: 4px solid #007bff;
        }

        .event-card .card-body {
            padding: 1rem;
        }

        .event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }

        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.875rem;
            color: #6c757d;
        }

        .event-actions {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }
    </style>
@endsection

@section('script')
    <script>
        let currentEventId = null;
        let deleteEventId = null;

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
            const form = document.getElementById('eventForm');
            const submitBtn = document.getElementById('submitBtn');
            
            form?.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!form.checkValidity()) {
                    form.classList.add('was-validated');
                    return;
                }
                
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.querySelector('.submit-text').classList.add('d-none');
                submitBtn.querySelector('.submitting-text').classList.remove('d-none');
                
                // Submit form
                if (currentEventId) {
                    updateEvent();
                } else {
                    createEvent();
                }
            });
        }

        function formatDateTimeLocal(date) {
            const offset = date.getTimezoneOffset() * 60000;
            const localDate = new Date(date.getTime() - offset);
            return localDate.toISOString().slice(0, 16);
        }

        function formatDateTimeForInput(dateTimeString) {
            if (!dateTimeString) return '';
            
            try {
                const date = new Date(dateTimeString);
                return formatDateTimeLocal(date);
            } catch (error) {
                return '';
            }
        }

        function formatDisplayDateTime(dateTimeString) {
            if (!dateTimeString) return '';
            
            try {
                const date = new Date(dateTimeString);
                return date.toLocaleString();
            } catch (error) {
                return dateTimeString;
            }
        }

        async function createEvent() {
            const form = document.getElementById('eventForm');
            const formData = new FormData(form);
            
            try {
                const response = await fetch('{{ route("calendar.store") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                    }
                });
                
                if (response.ok) {
                    showNotification('Event created successfully!', 'success');
                    resetForm();
                    loadEvents();
                } else {
                    const errorData = await response.json();
                    showNotification('Failed to create event: ' + (errorData.message || 'Unknown error'), 'danger');
                }
            } catch (error) {
                console.error('Error creating event:', error);
                showNotification('Failed to create event. Please try again.', 'danger');
            } finally {
                resetSubmitButton();
            }
        }

        async function updateEvent() {
            const form = document.getElementById('eventForm');
            const formData = new FormData(form);
            
            try {
                const response = await fetch(`{{ route("calendar.update", ":id") }}`.replace(':id', currentEventId), {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                        'X-HTTP-Method-Override': 'PUT'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Event updated successfully!', 'success');
                    resetForm();
                    loadEvents();
                } else {
                    showNotification('Failed to update event: ' + (data.message || 'Unknown error'), 'danger');
                }
            } catch (error) {
                console.error('Error updating event:', error);
                showNotification('Failed to update event. Please try again.', 'danger');
            } finally {
                resetSubmitButton();
            }
        }

        function editEvent(eventId) {
            // Scroll to form
            document.getElementById('eventForm').scrollIntoView({ behavior: 'smooth' });
            
            // Find event data from current events
            const eventContainer = document.querySelector(`[data-event-id="${eventId}"]`);
            if (!eventContainer) return;
            
            const eventData = JSON.parse(eventContainer.dataset.eventData);
            
            // Update form
            currentEventId = eventId;
            document.getElementById('form-title').textContent = 'Edit Event';
            document.getElementById('event_title').value = eventData.summary || '';
            document.getElementById('description').value = eventData.description || '';
            document.getElementById('start_time').value = formatDateTimeForInput(eventData.start?.dateTime || eventData.start?.date);
            document.getElementById('end_time').value = formatDateTimeForInput(eventData.end?.dateTime || eventData.end?.date);
            
            // Update button text
            document.getElementById('submitBtn').innerHTML = `
                <span class="submit-text">
                    <i class="ti ti-edit me-1"></i> Update Event
                </span>
                <span class="submitting-text d-none">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                    Updating...
                </span>
            `;
            
            // Show cancel button
            document.getElementById('cancelBtn').style.display = 'inline-block';
            
            // Update character counters
            setupCharacterCounters();
        }

        function resetForm() {
            currentEventId = null;
            document.getElementById('form-title').textContent = 'Create New Event';
            document.getElementById('eventForm').reset();
            document.getElementById('eventForm').classList.remove('was-validated');
            document.getElementById('cancelBtn').style.display = 'none';
            
            // Reset submit button
            document.getElementById('submitBtn').innerHTML = `
                <span class="submit-text">
                    <i class="ti ti-calendar-plus me-1"></i> Create Event
                </span>
                <span class="submitting-text d-none">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                    Creating...
                </span>
            `;
            
            // Reset datetime inputs
            initializeCalendar();
            setupCharacterCounters();
        }

        function resetSubmitButton() {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = false;
            submitBtn.querySelector('.submit-text').classList.remove('d-none');
            submitBtn.querySelector('.submitting-text').classList.add('d-none');
        }

        async function loadEvents() {
            const container = document.getElementById('events-container');
            container.innerHTML = `
                <div class="loading-spinner text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <span class="ms-2 text-muted">Loading your calendar events...</span>
                </div>
            `;
            
            try {
                const response = await fetch('{{ route("calendar.index") }}', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayEvents(data.events);
                } else {
                    container.innerHTML = `
                        <div class="text-center text-muted">
                            <i class="ti ti-alert-circle fs-2 mb-2 text-danger"></i>
                            <h6>Error Loading Events</h6>
                            <p>${data.message || 'Failed to load events'}</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading events:', error);
                container.innerHTML = `
                    <div class="text-center text-muted">
                        <i class="ti ti-alert-circle fs-2 mb-2 text-danger"></i>
                        <h6>Error Loading Events</h6>
                        <p>Failed to load events. Please try again.</p>
                    </div>
                `;
            }
        }

        function displayEvents(events) {
            const container = document.getElementById('events-container');
            
            if (!events || events.length === 0) {
                container.innerHTML = `
                    <div class="no-events">
                        <i class="ti ti-calendar-off"></i>
                        <h6>No Events Found</h6>
                        <p>You don't have any upcoming events. Create your first event above!</p>
                    </div>
                `;
                return;
            }
            
            const eventsHtml = events.map(event => `
                <div class="card event-card mb-3" data-event-id="${event.id}" data-event-data='${JSON.stringify(event)}'>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="event-title mb-2">${event.summary || 'Untitled Event'}</h6>
                                
                                <div class="event-meta">
                                    <div class="event-meta-item">
                                        <i class="ti ti-clock"></i>
                                        <span>
                                            ${formatDisplayDateTime(event.start?.dateTime || event.start?.date)}
                                            ${event.end?.dateTime || event.end?.date ? ' - ' + formatDisplayDateTime(event.end?.dateTime || event.end?.date) : ''}
                                        </span>
                                    </div>
                                    
                                    ${event.location ? `
                                        <div class="event-meta-item">
                                            <i class="ti ti-map-pin"></i>
                                            <span>${event.location}</span>
                                        </div>
                                    ` : ''}
                                </div>
                                
                                ${event.description ? `
                                    <div class="event-description mt-2">
                                        <i class="ti ti-file-text me-1"></i>
                                        ${event.description}
                                    </div>
                                ` : ''}
                            </div>
                            
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="ti ti-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="editEvent('${event.id}')">
                                            <i class="ti ti-edit me-2"></i>Edit
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="#" onclick="confirmDelete('${event.id}', '${(event.summary || 'Untitled Event').replace(/'/g, '\\\'')}')" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                            <i class="ti ti-trash me-2"></i>Delete
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = eventsHtml;
        }

        function confirmDelete(eventId, eventTitle) {
            deleteEventId = eventId;
            document.getElementById('delete-event-title').textContent = eventTitle;
            
            // Set up the confirm delete button
            document.getElementById('confirmDeleteBtn').onclick = function() {
                deleteEvent(eventId);
            };
        }

      




        







        async function deleteEvent(eventId) {
            try {
                const response = await fetch(`{{ route("calendar.destroy", ":id") }}`.replace(':id', eventId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Event deleted successfully!
















         showNotification('Event deleted successfully!', 'success');
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                    modal.hide();
                    
                    // Reload events
                    loadEvents();
                    
                    // Reset form if editing the deleted event
                    if (currentEventId === eventId) {
                        resetForm();
                    }
                } else {
                    showNotification('Failed to delete event: ' + (data.message || 'Unknown error'), 'danger');
                }
            } catch (error) {
                console.error('Error deleting event:', error);
                showNotification('Failed to delete event. Please try again.', 'danger');
            }
        }

        function refreshEvents() {
            loadEvents();
        }

        async function disconnectGoogle() {
            if (!confirm('Are you sure you want to disconnect your Google account?')) return;
            try {
                const response = await fetch('{{ route("google.disconnect") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                });
                if (response.ok) window.location.reload();
            } catch (error) {
                console.error('Error disconnecting:', error);
                showNotification('Failed to disconnect Google account', 'danger');
            }
        }

        function showNotification(message, type = 'info') {
            // Remove existing notifications
            const existingAlerts = document.querySelectorAll('.alert.alert-dismissible');
            existingAlerts.forEach(alert => {
                if (alert.getAttribute('role') === 'alert') {
                    alert.remove();
                }
            });
            
            const alertClass = type === 'success' ? 'alert-success' : 
                              type === 'danger' ? 'alert-danger' : 
                              type === 'warning' ? 'alert-warning' : 'alert-info';
            
            const icon = type === 'success' ? 'ti-check' : 
                        type === 'danger' ? 'ti-alert-circle' : 
                        type === 'warning' ? 'ti-alert-triangle' : 'ti-info-circle';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible" role="alert">
                    <i class="ti ${icon} me-2"></i>
                    <strong>${type.charAt(0).toUpperCase() + type.slice(1)}!</strong> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Insert at the top of the section
            const section = document.querySelector('section');
            const firstCard = section.querySelector('.card');
            firstCard.insertAdjacentHTML('beforebegin', alertHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const alert = document.querySelector('.alert.alert-dismissible[role="alert"]');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }

        // Utility function to handle API errors
        function handleApiError(error, defaultMessage = 'An error occurred') {
            console.error('API Error:', error);
            
            if (error.response) {
                error.response.json().then(data => {
                    showNotification(data.message || defaultMessage, 'danger');
                }).catch(() => {
                    showNotification(defaultMessage, 'danger');
                });
            } else {
                showNotification(defaultMessage, 'danger');
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + S to save form
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                const form = document.getElementById('eventForm');
                if (form) {
                    form.dispatchEvent(new Event('submit'));
                }
            }
            
            // Escape to cancel editing
            if (e.key === 'Escape' && currentEventId) {
                resetForm();
            }
        });

        // Handle form submission errors
        window.addEventListener('load', function() {
            @if($errors->any())
                showNotification('Please fix the form errors below', 'danger');
            @endif
            
            @if(session('success'))
                showNotification('{{ session('success') }}', 'success');
            @endif
            
            @if(session('error'))
                showNotification('{{ session('error') }}', 'danger');
            @endif
        });

        // Auto-refresh events every 5 minutes if connected
        @if($isConnected)
            setInterval(function() {
                if (document.visibilityState === 'visible') {
                    loadEvents();
                }
            }, 300000); // 5 minutes
        @endif

        // Handle visibility change to refresh events when user comes back
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden && {{ $isConnected ? 'true' : 'false' }}) {
                loadEvents();
            }
        });

        // Prevent form submission on Enter key in input fields (except textarea)
        document.querySelectorAll('#eventForm input').forEach(input => {
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });
        });

        // Add smooth scrolling for better UX
        function scrollToForm() {
            document.getElementById('eventForm').scrollIntoView({ 
                behavior: 'smooth',
                block: 'start'
            });
        }

        // Add loading state to refresh button
        function addLoadingState(button) {
            const originalHtml = button.innerHTML;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading...';
            button.disabled = true;
            
            return function() {
                button.innerHTML = originalHtml;
                button.disabled = false;
            };
        }

        // Enhanced loadEvents with loading state
        const originalLoadEvents = loadEvents;
        loadEvents = async function() {
            const refreshBtn = document.querySelector('[onclick="loadEvents()"]');
            const removeLoading = refreshBtn ? addLoadingState(refreshBtn) : null;
            
            try {
                await originalLoadEvents();
            } finally {
                if (removeLoading) {
                    removeLoading();
                }
            }
        };

        // Add tooltips to buttons
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Add confirmation for navigating away with unsaved changes
        let formChanged = false;
        document.getElementById('eventForm')?.addEventListener('input', function() {
            formChanged = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged && currentEventId) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                return e.returnValue;
            }
        });

        // Reset form changed flag on successful submission
        const originalCreateEvent = createEvent;
        createEvent = async function() {
            const result = await originalCreateEvent();
            formChanged = false;
            return result;
        };

        const originalUpdateEvent = updateEvent;
        updateEvent = async function() {
            const result = await originalUpdateEvent();
            formChanged = false;
            return result;
        };

        // Add real-time validation
        document.getElementById('event_title')?.addEventListener('input', function() {
            const isValid = this.value.length > 0 && this.value.length <= 100;
            this.classList.toggle('is-valid', isValid);
            this.classList.toggle('is-invalid', !isValid);
        });

        document.getElementById('start_time')?.addEventListener('change', function() {
            const endTime = document.getElementById('end_time');
            const isValid = this.value && (!endTime.value || new Date(this.value) < new Date(endTime.value));
            this.classList.toggle('is-valid', isValid);
            this.classList.toggle('is-invalid', !isValid);
        });

        document.getElementById('end_time')?.addEventListener('change', function() {
            const startTime = document.getElementById('start_time');
            const isValid = this.value && startTime.value && new Date(startTime.value) < new Date(this.value);
            this.classList.toggle('is-valid', isValid);
            this.classList.toggle('is-invalid', !isValid);
        });

    </script>
@endsection           