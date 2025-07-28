@extends('admin.layouts.master')
@section('title', 'Google Forms')
@section('content')
    <section>
        <h4 class="py-3 mb-4">
            <span class="text-muted fw-light">
                Google /
            </span> 
            Google Forms
        </h4>

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

        <!-- Connection Status Card -->
        <div class="card mb-5">
            <div class="card-header">
                <h5 class="mb-0">Google Connection Status</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="d-flex align-items-center" id="connection-status">
                        <i class="fas fa-plug" id="connection-icon" style="color: red;"></i>
                        <span class="ms-2" id="connection-text">Checking connection...</span>
                    </span>
                    <div id="connection-actions">
                        <!-- Actions will be loaded dynamically -->
                    </div>
                </div>
                <div id="connection-message" class="mt-3" style="display: none;">
                    <!-- Connection message will be shown here -->
                </div>
            </div>
        </div>

        <!-- Forms Management Section -->
        @if(!isset($form) && !isset($responses))
            <!-- Forms List View -->
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4>Google Forms Management</h4>
                                <button id="create-form-btn" class="btn btn-primary" style="display: none;" onclick="showCreateForm()">
                                    <i class="fas fa-plus"></i> Create New Form
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="not-connected-alert" class="alert alert-warning" style="display: none;">
                                    <h5><i class="fas fa-exclamation-triangle"></i> Google Account Not Connected</h5>
                                    <p>You need to connect your Google account to manage Google Forms.</p>
                                    <a href="{{ route('google.redirect') }}" class="btn btn-success">
                                        <i class="fab fa-google"></i> Connect Google Account
                                    </a>
                                </div>
                                
                                <div id="connected-content" style="display: none;">
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle"></i> Google account connected successfully!
                                    </div>
                                    
                                    <div id="forms-container">
                                        <p>Use the "Create New Form" button above to create your first Google Form.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create Form Modal -->
            <div class="modal fade" id="createFormModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Create New Google Form</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="create-form">
                                @csrf
                                <div class="mb-3">
                                    <label for="title" class="form-label">Form Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required maxlength="255">
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Form Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" maxlength="1000"></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="createForm()">Create Form</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(isset($form) && !isset($responses))
            <!-- Edit Form View -->
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4>Edit Form: {{ $form['title'] }}</h4>
                                <div>
                                    <a href="{{ $form['edit_url'] }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-external-link-alt"></i> Edit in Google Forms
                                    </a>
                                    <a href="{{ route('forms.responses.show', $formId) }}" class="btn btn-info btn-sm">
                                        <i class="fas fa-chart-bar"></i> View Responses
                                    </a>
                                </div>
                            </div>

                            <div class="card-body">
                                <!-- Form Info Update -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h5>Form Information</h5>
                                        <form id="update-info-form">
                                            @csrf
                                            @method('PUT')
                                            <div class="mb-3">
                                                <label for="form_title" class="form-label">Title</label>
                                                <input type="text" class="form-control" id="form_title" name="title" value="{{ $form['title'] }}" required maxlength="255">
                                            </div>
                                            <div class="mb-3">
                                                <label for="form_description" class="form-label">Description</label>
                                                <textarea class="form-control" id="form_description" name="description" rows="2" maxlength="1000">{{ $form['description'] ?? '' }}</textarea>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-sm">Update Info</button>
                                        </form>
                                    </div>

                                    <div class="col-md-6">
                                        <h5>Form Links</h5>
                                        <div class="mb-2">
                                            <label class="form-label">Public Form URL:</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" value="{{ $form['published_url'] }}" readonly>
                                                <button class="btn btn-outline-secondary" onclick="copyToClipboard('{{ $form['published_url'] }}')">Copy</button>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Edit URL:</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" value="{{ $form['edit_url'] }}" readonly>
                                                <button class="btn btn-outline-secondary" onclick="copyToClipboard('{{ $form['edit_url'] }}')">Copy</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <!-- Add Questions -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>Add Text Question</h5>
                                        <form id="add-text-question-form">
                                            @csrf
                                            <div class="mb-3">
                                                <label for="text_title" class="form-label">Question Title</label>
                                                <input type="text" class="form-control" id="text_title" name="title" required maxlength="255">
                                            </div>
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" id="text_required" name="required" value="1">
                                                <label class="form-check-label" for="text_required">Required</label>
                                            </div>
                                            <button type="submit" class="btn btn-success btn-sm">Add Text Question</button>
                                        </form>
                                    </div>

                                    <div class="col-md-6">
                                        <h5>Add Multiple Choice Question</h5>
                                        <form id="add-choice-question-form">
                                            @csrf
                                            <div class="mb-3">
                                                <label for="choice_title" class="form-label">Question Title</label>
                                                <input type="text" class="form-control" id="choice_title" name="title" required maxlength="255">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Options (minimum 2 required)</label>
                                                <div id="options-container">
                                                    <input type="text" class="form-control mb-2" name="options[]" placeholder="Option 1" required maxlength="255">
                                                    <input type="text" class="form-control mb-2" name="options[]" placeholder="Option 2" required maxlength="255">
                                                </div>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addOption()">Add Option</button>
                                            </div>
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" class="form-check-input" id="choice_required" name="required" value="1">
                                                <label class="form-check-label" for="choice_required">Required</label>
                                            </div>
                                            <button type="submit" class="btn btn-success btn-sm">Add Choice Question</button>
                                        </form>
                                    </div>
                                </div>

                                <hr>

                                <!-- Current Questions -->
                                <div class="mt-4">
                                    <h5>Current Questions</h5>
                                    @if(isset($form['items']) && count($form['items']) > 0)
                                        <div class="list-group">
                                            @foreach($form['items'] as $item)
                                                <div class="list-group-item">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1">{{ $item['title'] }}</h6>
                                                        @if(isset($item['question']))
                                                            <small class="text-muted">
                                                                {{ ucfirst($item['question']['type']) }}
                                                                @if($item['question']['required']) - Required @endif
                                                            </small>
                                                        @endif
                                                    </div>
                                                    @if(!empty($item['description']))
                                                        <p class="mb-1">{{ $item['description'] }}</p>
                                                    @endif
                                                    @if(isset($item['question']['options']))
                                                        <small class="text-muted">Options: {{ implode(', ', $item['question']['options']) }}</small>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted">No questions added yet. Use the forms above to add questions.</p>
                                    @endif
                                </div>

                                <div class="mt-4">
                                    <a href="{{ route('forms.index') }}" class="btn btn-secondary">Back to Forms</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(isset($responses))
            <!-- Form Responses View -->
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4>Form Responses: {{ $form['title'] }}</h4>
                                <div>
                                    <a href="{{ route('forms.edit', $formId) }}" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-edit"></i> Edit Form
                                    </a>
                                    <a href="{{ $form['edit_url'] }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-external-link-alt"></i> View in Google Forms
                                    </a>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="mb-3">
                                    <h5>Total Responses: {{ $responses['total_responses'] }}</h5>
                                    @if(!empty($form['description']))
                                        <p class="text-muted">{{ $form['description'] }}</p>
                                    @endif
                                </div>

                                @if($responses['total_responses'] > 0)
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Response ID</th>
                                                    <th>Submitted</th>
                                                    <th>Last Modified</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($responses['responses'] as $response)
                                                    <tr>
                                                        <td>{{ substr($response['response_id'], 0, 8) }}...</td>
                                                        <td>{{ $response['create_time'] ? date('M j, Y g:i A', strtotime($response['create_time'])) : 'N/A' }}</td>
                                                        <td>{{ $response['last_submitted_time'] ? date('M j, Y g:i A', strtotime($response['last_submitted_time'])) : 'N/A' }}</td>
                                                        <td>
                                                            <button class="btn btn-sm btn-info" onclick="viewResponse('{{ $response['response_id'] }}')">
                                                                <i class="fas fa-eye"></i> View Details
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info">
                                        <h5>No Responses Yet</h5>
                                        <p>This form hasn't received any responses yet. Share your form URL to start collecting responses:</p>
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="{{ $form['published_url'] }}" readonly>
                                            <button class="btn btn-outline-secondary" onclick="copyToClipboard('{{ $form['published_url'] }}')">Copy Link</button>
                                        </div>
                                    </div>
                                @endif

                                <div class="mt-4">
                                    <a href="{{ route('forms.index') }}" class="btn btn-secondary">Back to Forms</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Response Details Modal -->
        <div class="modal fade" id="responseModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Response Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="responseModalBody">
                        <!-- Response details will be loaded here -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

    </section>

@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            checkConnectionStatus();
        });

        function checkConnectionStatus() {
            fetch('{{ route("google.forms.status") }}', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateConnectionUI(data.data.connected, data.data.connect_url);
                }
            })
            .catch(error => {
                console.error('Error checking connection:', error);
            });
        }

        function updateConnectionUI(connected, connectUrl) {
            const icon = document.getElementById('connection-icon');
            const text = document.getElementById('connection-text');
            const actions = document.getElementById('connection-actions');
            const message = document.getElementById('connection-message');
            const notConnectedAlert = document.getElementById('not-connected-alert');
            const connectedContent = document.getElementById('connected-content');
            const createFormBtn = document.getElementById('create-form-btn');

            if (connected) {
                icon.style.color = 'green';
                text.textContent = 'Google Calendar Connected';
                message.innerHTML = '<div class="alert alert-success">Ready to create and manage Google Forms</div>';
                message.style.display = 'block';
                
                if (notConnectedAlert) notConnectedAlert.style.display = 'none';
                if (connectedContent) connectedContent.style.display = 'block';
                if (createFormBtn) createFormBtn.style.display = 'inline-block';
                
                actions.innerHTML = `
                    <button class="btn btn-outline-secondary btn-sm" onclick="disconnectGoogle()">
                        <i class="ti ti-unlink"></i> Disconnect
                    </button>
                `;
            } else {
                icon.style.color = 'red';
                text.textContent = 'Google Calendar Not Connected';
                message.innerHTML = '<div class="alert alert-danger">Connect your Google account to manage Google Forms</div>';
                message.style.display = 'block';
                
                if (notConnectedAlert) notConnectedAlert.style.display = 'block';
                if (connectedContent) connectedContent.style.display = 'none';
                if (createFormBtn) createFormBtn.style.display = 'none';
                
                actions.innerHTML = `
                    <a href="${connectUrl}" class="btn btn-outline-primary btn-sm">
                        <i class="ti ti-brand-google"></i> Connect Google Account
                    </a>
                `;
            }
        }

        function showCreateForm() {
            const modal = new bootstrap.Modal(document.getElementById('createFormModal'));
            modal.show();
        }

        function createForm() {
            const form = document.getElementById('create-form');
            const formData = new FormData(form);
            
            fetch('{{ route("forms.store") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Form created successfully!');
                    window.location.href = '{{ route("forms.index") }}';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while creating the form.');
            });
        }

        @if(isset($formId))
        // Update form info
        document.getElementById('update-info-form').addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm(this, '{{ route("forms.update", $formId) }}', 'PUT');
        });

        // Add text question
        document.getElementById('add-text-question-form').addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm(this, '{{ route("forms.questions.text", $formId) }}', 'POST', true);
        });

        // Add choice question
        document.getElementById('add-choice-question-form').addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm(this, '{{ route("forms.questions.choice", $formId) }}', 'POST', true);
        });
        @endif

        function submitForm(form, url, method, clearForm = false) {
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Processing...';
            
            const fetchOptions = {
                method: method,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: formData
            };

            if (method === 'PUT') {
                fetchOptions.headers['X-HTTP-Method-Override'] = 'PUT';
            }
            
            fetch(url, fetchOptions)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    if (clearForm) {
                        form.reset();
                    }
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred.');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        }

        function addOption() {
            const container = document.getElementById('options-container');
            const optionCount = container.children.length + 1;
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control mb-2';
            input.name = 'options[]';
            input.placeholder = 'Option ' + optionCount;
            input.required = true;
            input.maxLength = 255;
            container.appendChild(input);
        }

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Copied to clipboard!');
            });
        }

        function viewResponse(responseId) {
            document.getElementById('responseModalBody').innerHTML = `
                <p><strong>Response ID:</strong> ${responseId}</p>
                <p><em>Detailed response viewing would be implemented here with additional API calls.</em></p>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('responseModal'));
            modal.show();
        }

        function disconnectGoogle() {
            if (confirm('Are you sure you want to disconnect your Google account?')) {
                // Implement disconnect functionality
                alert('Disconnect functionality would be implemented here.');
            }
        }
    </script>
@endsection