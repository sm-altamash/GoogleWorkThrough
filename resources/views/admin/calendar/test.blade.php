@extends('layouts.app')

@section('title', 'Google Calendar Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Google Auth Check -->
            @if(!auth()->user()->googleToken)
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> 
                    You need to connect your Google account to use the calendar.
                    <a href="{{ route('google.auth') }}" class="btn btn-primary btn-sm ms-2">
                        <i class="fab fa-google"></i> Connect Google Account
                    </a>
                </div>
            @endif

            <!-- Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-calendar-alt"></i> My Calendar
                </h1>
                @if(auth()->user()->googleToken)
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
                        <i class="fas fa-plus"></i> Create Event
                    </button>
                @endif
            </div>

            <!-- Alert Messages -->
            <div id="alertContainer"></div>

            <!-- Calendar Events Section -->
            @if(auth()->user()->googleToken)
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Upcoming Events</h6>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="loadEvents()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    <!-- Loading Spinner -->
                    <div id="loadingSpinner" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading your events...</p>
                    </div>

                    <!-- Events Container -->
                    <div id="eventsContainer">
                        <!-- Events will be loaded here -->
                    </div>
                </div>
            </div>
            @else
                <div class="card shadow mb-4">
                    <div class="card-body text-center py-5">
                        <i class="fab fa-google fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Connect Your Google Account</h5>
                        <p class="text-muted">To view and manage your calendar events, please connect your Google account.</p>
                        <a href="{{ route('google.auth') }}" class="btn btn-primary">
                            <i class="fab fa-google"></i> Connect Google Account
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Create Event Modal -->
<div class="modal fade" id="createEventModal" tabindex="-1" aria-labelledby="createEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEventModalLabel">Create New Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createEventForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="eventTitle" class="form-label">Event Title *</label>
                            <input type="text" class="form-control" id="eventTitle" name="title" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="eventDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="eventDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="eventStartTime" class="form-label">Start Time *</label>
                            <input type="datetime-local" class="form-control" id="eventStartTime" name="start_time" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="eventEndTime" class="form-label">End Time *</label>
                            <input type="datetime-local" class="form-control" id="eventEndTime" name="end_time" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="eventTimezone" class="form-label">Timezone</label>
                            <select class="form-control" id="eventTimezone" name="timezone">
                                <option value="UTC">UTC</option>
                                <option value="America/New_York">Eastern Time</option>
                                <option value="America/Chicago">Central Time</option>
                                <option value="America/Denver">Mountain Time</option>
                                <option value="America/Los_Angeles">Pacific Time</option>
                                <option value="Europe/London">London</option>
                                <option value="Asia/Tokyo">Tokyo</option>
                                <option value="Asia/Karachi">Pakistan Time</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="submit-spinner spinner-border spinner-border-sm me-2" style="display: none;"></span>
                        Create Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Event Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEventModalLabel">Edit Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editEventForm">
                <input type="hidden" id="editEventId" name="event_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="editEventTitle" class="form-label">Event Title *</label>
                            <input type="text" class="form-control" id="editEventTitle" name="title" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="editEventDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editEventDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editEventStartTime" class="form-label">Start Time *</label>
                            <input type="datetime-local" class="form-control" id="editEventStartTime" name="start_time" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editEventEndTime" class="form-label">End Time *</label>
                            <input type="datetime-local" class="form-control" id="editEventEndTime" name="end_time" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="editEventTimezone" class="form-label">Timezone</label>
                            <select class="form-control" id="editEventTimezone" name="timezone">
                                <option value="UTC">UTC</option>
                                <option value="America/New_York">Eastern Time</option>
                                <option value="America/Chicago">Central Time</option>
                                <option value="America/Denver">Mountain Time</option>
                                <option value="America/Los_Angeles">Pacific Time</option>
                                <option value="Europe/London">London</option>
                                <option value="Asia/Tokyo">Tokyo</option>
                                <option value="Asia/Karachi">Pakistan Time</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="submit-spinner spinner-border spinner-border-sm me-2" style="display: none;"></span>
                        Update Event
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteEventModal" tabindex="-1" aria-labelledby="deleteEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteEventModalLabel">Delete Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this event?</p>
                <p class="text-muted"><strong id="deleteEventTitle"></strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <span class="submit-spinner spinner-border spinner-border-sm me-2" style="display: none;"></span>
                    Delete Event
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Initialize the calendar only if user has Google token
            @if(auth()->user()->googleToken)
                loadEvents();
            @endif
            
            // Set default timezone to Pakistan
            $('#eventTimezone').val('Asia/Karachi');
            $('#editEventTimezone').val('Asia/Karachi');
            
            // Set minimum date/time to current date/time
            setMinDateTime();
        });

        /**
         * Load events from the server
         * This function fetches events using AJAX and displays them
         */
        function loadEvents() {
            $('#loadingSpinner').show();
            $('#eventsContainer').hide();
            
            $.ajax({
                url: '{{ route("calendar.index") }}',
                type: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        displayEvents(response.events);
                    } else {
                        showAlert('error', 'Failed to load events: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading events:', error);
                    showAlert('error', 'Failed to load events. Please try again.');
                },
                complete: function() {
                    $('#loadingSpinner').hide();
                    $('#eventsContainer').show();
                }
            });
        }

        /**
         * Display events in the UI
         * This function takes the events array and creates HTML elements
         */
        function displayEvents(events) {
            const container = $('#eventsContainer');
            
            if (!events || events.length === 0) {
                container.html(`
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No upcoming events</h5>
                        <p class="text-muted">Create your first event to get started!</p>
                    </div>
                `);
                return;
            }
            
            let html = '<div class="row">';
            
            events.forEach(function(event) {
                const startTime = new Date(event.start.dateTime || event.start.date);
                const endTime = new Date(event.end.dateTime || event.end.date);
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card h-100 event-card">
                            <div class="card-body">
                                <h6 class="card-title">${event.summary || 'Untitled Event'}</h6>
                                <p class="card-text text-muted small mb-2">
                                    <i class="fas fa-clock"></i> 
                                    ${formatDateTime(startTime)} - ${formatDateTime(endTime)}
                                </p>
                                ${event.description ? `<p class="card-text">${event.description}</p>` : ''}
                                <div class="d-flex justify-content-end">
                                    <button class="btn btn-sm btn-outline-primary me-2" onclick="editEvent('${event.id}')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteEvent('${event.id}', '${event.summary || 'Untitled Event'}')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.html(html);
        }

        /**
         * Handle create event form submission
         */
        $('#createEventForm').on('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = $(this).find('button[type="submit"]');
            const spinner = submitBtn.find('.submit-spinner');
            
            // Show loading state
            spinner.show();
            submitBtn.prop('disabled', true);
            
            // Get form data
            const formData = {
                title: $('#eventTitle').val(),
                description: $('#eventDescription').val(),
                start_time: $('#eventStartTime').val(),
                end_time: $('#eventEndTime').val(),
                timezone: $('#eventTimezone').val()
            };
            
            // Validate end time is after start time
            if (new Date(formData.end_time) <= new Date(formData.start_time)) {
                showAlert('error', 'End time must be after start time');
                spinner.hide();
                submitBtn.prop('disabled', false);
                return;
            }
            
            $.ajax({
                url: '{{ route("calendar.store") }}',
                type: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Event created successfully!');
                        $('#createEventModal').modal('hide');
                        $('#createEventForm')[0].reset();
                        loadEvents(); // Refresh the events list
                    } else {
                        showAlert('error', 'Failed to create event: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error creating event:', error);
                    const errorMessage = xhr.responseJSON?.message || 'Failed to create event. Please try again.';
                    showAlert('error', errorMessage);
                },
                complete: function() {
                    spinner.hide();
                    submitBtn.prop('disabled', false);
                }
            });
        });

        /**
         * Handle edit event
         */
        function editEvent(eventId) {
            // Here you would typically fetch the event details first
            // For now, we'll show the modal and let user edit
            $('#editEventId').val(eventId);
            $('#editEventModal').modal('show');
        }

        /**
         * Handle edit event form submission
         */
        $('#editEventForm').on('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = $(this).find('button[type="submit"]');
            const spinner = submitBtn.find('.submit-spinner');
            const eventId = $('#editEventId').val();
            
            // Show loading state
            spinner.show();
            submitBtn.prop('disabled', true);
            
            // Get form data
            const formData = {
                title: $('#editEventTitle').val(),
                description: $('#editEventDescription').val(),
                start_time: $('#editEventStartTime').val(),
                end_time: $('#editEventEndTime').val(),
                timezone: $('#editEventTimezone').val()
            };
            
            // Validate end time is after start time
            if (new Date(formData.end_time) <= new Date(formData.start_time)) {
                showAlert('error', 'End time must be after start time');
                spinner.hide();
                submitBtn.prop('disabled', false);
                return;
            }
            
            $.ajax({
                url: '{{ route("calendar.update", ":id") }}'.replace(':id', eventId),
                type: 'PUT',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Event updated successfully!');
                        $('#editEventModal').modal('hide');
                        loadEvents(); // Refresh the events list
                    } else {
                        showAlert('error', 'Failed to update event: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error updating event:', error);
                    const errorMessage = xhr.responseJSON?.message || 'Failed to update event. Please try again.';
                    showAlert('error', errorMessage);
                },
                complete: function() {
                    spinner.hide();
                    submitBtn.prop('disabled', false);
                }
            });
        });

        /**
         * Handle delete event
         */
        function deleteEvent(eventId, eventTitle) {
            $('#deleteEventTitle').text(eventTitle);
            $('#deleteEventModal').modal('show');
            
            // Store event ID for deletion
            $('#confirmDeleteBtn').data('event-id', eventId);
        }

        /**
         * Handle delete confirmation
         */
        $('#confirmDeleteBtn').on('click', function() {
            const eventId = $(this).data('event-id');
            const spinner = $(this).find('.submit-spinner');
            
            // Show loading state
            spinner.show();
            $(this).prop('disabled', true);
            
            $.ajax({
                url: '{{ route("calendar.destroy", ":id") }}'.replace(':id', eventId),
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Event deleted successfully!');
                        $('#deleteEventModal').modal('hide');
                        loadEvents(); // Refresh the events list
                    } else {
                        showAlert('error', 'Failed to delete event: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error deleting event:', error);
                    const errorMessage = xhr.responseJSON?.message || 'Failed to delete event. Please try again.';
                    showAlert('error', errorMessage);
                },
                complete: function() {
                    spinner.hide();
                    $('#confirmDeleteBtn').prop('disabled', false);
                }
            });
        });

        /**
         * Utility function to show alerts
         */
        function showAlert(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const iconClass = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    <i class="${iconClass}"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            $('#alertContainer').html(alertHtml);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $('#alertContainer .alert').alert('close');
            }, 5000);
        }

        /**
         * Utility function to format date and time
         */
        function formatDateTime(date) {
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        /**
         * Set minimum date/time to current date/time
         */
        function setMinDateTime() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hour = String(now.getHours()).padStart(2, '0');
            const minute = String(now.getMinutes()).padStart(2, '0');
            
            const minDateTime = `${year}-${month}-${day}T${hour}:${minute}`;
            
            $('#eventStartTime').attr('min', minDateTime);
            $('#eventEndTime').attr('min', minDateTime);
            $('#editEventStartTime').attr('min', minDateTime);
            $('#editEventEndTime').attr('min', minDateTime);
        }

        /**
         * Reset form when modal is closed
         */
        $('#createEventModal').on('hidden.bs.modal', function () {
            $('#createEventForm')[0].reset();
            $('#eventTimezone').val('Asia/Karachi');
        });

        $('#editEventModal').on('hidden.bs.modal', function () {
            $('#editEventForm')[0].reset();
            $('#editEventTimezone').val('Asia/Karachi');
        });
    </script>

    <style>
        .event-card {
            transition: all 0.3s ease;
            border: 1px solid #e3e6f0;
        }

        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .modal-content {
            border-radius: 10px;
        }

        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }

        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }

        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2e59d9;
        }

        .alert {
            border-radius: 10px;
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
        }
    </style>
@endsection