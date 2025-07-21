@section('content')
@extends('admin.layouts.master')
@section('title', 'Gmail Integration')

@section('content')
<div class="container-fluid">
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">
            Mails /
        </span>
        Google Gmail
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

    @if($isConnected)
        <div class="row">
            <!-- Email List Card -->
            <div class="col-md-4 col-12 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Inbox</h5>
                        <button class="btn btn-primary btn-sm" onclick="refreshEmails()">
                            <i class="ti ti-refresh me-1"></i> Refresh
                        </button>
                    </div>
                    <div class="card-body emails-container" style="max-height: 600px; overflow-y: auto;">
                        <div id="emails-loading" class="text-center py-3 d-none">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div id="emails-list"></div>
                    </div>
                </div>
            </div>

            <!-- Compose Email Card -->
            <div class="col-md-8 col-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Compose Email</h5>
                    </div>
                    <div class="card-body">
                        <form id="emailForm" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">To</label>
                                <input type="email" class="form-control" name="to" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <input type="text" class="form-control" name="subject" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea class="form-control" name="body" rows="8" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Attachments</label>
                                <input type="file" class="form-control" name="attachments[]" multiple>
                                <small class="text-muted">Max file size: 10MB</small>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-send me-1"></i> Send
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="saveDraft()">
                                    <i class="ti ti-file me-1"></i> Save as Draft
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@section('script')
@if($isConnected)
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            refreshEmails();
            setupFormHandlers();
        });

        function refreshEmails() {
            const container = document.getElementById('emails-list');
            const loading = document.getElementById('emails-loading');
            
            loading.classList.remove('d-none');
            container.innerHTML = '';

            fetch('{{ route("gmail.index") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages) {
                        container.innerHTML = data.messages.map(email => `
                            <div class="email-item card mb-2">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted">${email.date}</small>
                                    </div>
                                    <div class="fw-semibold">${email.subject || '(no subject)'}</div>
                                    <div class="text-muted small">From: ${email.from}</div>
                                    <div class="text-muted small text-truncate">${email.snippet}</div>
                                </div>
                            </div>
                        `).join('');
                    }
                })
                .catch(error => {
                    console.error('Error fetching emails:', error);
                    container.innerHTML = `
                        <div class="alert alert-danger">
                            Failed to load emails: ${error.message || 'Check permissions.'}
                        </div>
                    `;
                })
                .finally(() => {
                    loading.classList.add('d-none');
                });
        }

        function setupFormHandlers() {
            const form = document.getElementById('emailForm');
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                sendEmail(new FormData(this));
            });
        }

        function sendEmail(formData) {
            const submitBtn = document.querySelector('#emailForm button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Sending...`;

            fetch('{{ route("gmail.send") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Email sent successfully');
                    form.reset();
                    refreshEmails();
                } else {
                    showToast('error', data.message || 'Failed to send email');
                }
            })
            .catch(error => {
                console.error('Error sending email:', error);
                showToast('error', 'Failed to send email');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        }

        function saveDraft(formData) {
            const form = document.getElementById('emailForm');
            const formData = new FormData(form);
            
            fetch('{{ route("gmail.draft") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Draft saved successfully');
                } else {
                    showToast('error', data.message || 'Failed to save draft');
                }
            })
            .catch(error => {
                console.error('Error saving draft:', error);
                showToast('error', 'Failed to save draft');
            });
        }

        function showToast(type, message) {
            // If you have a toast notification system
            if (typeof Toastify === 'function') {
                Toastify({
                    text: message,
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    backgroundColor: type === 'success' ? '#28a745' : '#dc3545'
                }).showToast();
            } else {
                alert(message);
            }
        }
            
    </script>


    <style>
        .email-item {
            transition: all 0.2s;
            cursor: pointer;
        }
        .email-item:hover {
            background-color: rgba(0,0,0,0.05);
        }
        .emails-container::-webkit-scrollbar {
            width: 5px;
        }
        .emails-container::-webkit-scrollbar-thumb {
            background-color: rgba(0,0,0,0.2);
            border-radius: 10px;
        }
    </style>
@endif
@endsection