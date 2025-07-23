@extends('admin.layouts.master')
@section('title', 'Google Classroom')

@section('content')
<div class="container-fluid">
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">Google Services /</span> Classroom
    </h4>

    <!-- Connection Status -->
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
        <!-- Quick Actions -->
        <div class="row">
            <!-- Dashboard Stats -->
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <i class="ti ti-books fs-3 me-2 text-primary"></i>
                                    <div>
                                        <div class="small text-muted">Total Courses</div>
                                        <h5 class="mb-0">{{ $coursesCount ?? 0 }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="d-flex gap-2 justify-content-end">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCourseModal">
                                        <i class="ti ti-plus me-1"></i> Create Course
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#inviteModal">
                                        <i class="ti ti-mail me-1"></i> Send Invitation
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="refreshData()">
                                        <i class="ti ti-refresh me-1"></i> Refresh
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Courses List -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Your Courses</h5>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                Filter Status
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ route('classroom.courses.index', ['courseStates' => 'ACTIVE']) }}">Active</a>
                                <a class="dropdown-item" href="{{ route('classroom.courses.index', ['courseStates' => 'ARCHIVED']) }}">Archived</a>
                                <a class="dropdown-item" href="{{ route('classroom.courses.index') }}">All</a>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Course Name</th>
                                    <th>Section</th>
                                    <th>Status</th>
                                    <th>Students</th>
                                    <th>Coursework</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                                @forelse($courses ?? [] as $course)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="ti ti-book me-2"></i>
                                                <a href="{{ route('classroom.courses.show', $course['id']) }}">
                                                    {{ $course['name'] }}
                                                </a>
                                            </div>
                                        </td>
                                        <td>{{ $course['section'] ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $course['courseState'] === 'ACTIVE' ? 'success' : 'secondary' }}">
                                                {{ $course['courseState'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('classroom.students.index', $course['id']) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="ti ti-users me-1"></i> 
                                                Students
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('classroom.coursework.index', $course['id']) }}" 
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="ti ti-clipboard me-1"></i> 
                                                Coursework
                                            </a>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm px-0" data-bs-toggle="dropdown">
                                                    <i class="ti ti-dots-vertical"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="{{ route('classroom.courses.show', $course['id']) }}">
                                                        <i class="ti ti-eye me-1"></i> View Details
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('classroom.teachers.index', $course['id']) }}">
                                                        <i class="ti ti-user me-1"></i> Teachers
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('classroom.courses.edit', $course['id']) }}">
                                                        <i class="ti ti-edit me-1"></i> Edit
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <form action="{{ route('classroom.courses.destroy', $course['id']) }}" 
                                                          method="POST" 
                                                          onsubmit="return confirm('Are you sure you want to archive this course?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="ti ti-archive me-1"></i> Archive
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-center">
                                                <i class="ti ti-school fs-2 text-muted mb-2"></i>
                                                <p class="mb-0">No courses found</p>
                                                <small class="text-muted">Create your first course to get started</small>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Create Course Modal -->
<div class="modal fade" id="createCourseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('classroom.courses.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create New Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Course Name</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               name="name" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Section</label>
                        <input type="text" class="form-control @error('section') is-invalid @enderror" 
                               name="section">
                        @error('section')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  name="description" rows="3"></textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Room</label>
                        <input type="text" class="form-control @error('room') is-invalid @enderror" 
                               name="room">
                        @error('room')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Invite Modal -->
<div class="modal fade" id="inviteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('classroom.invitations.store', $course['id'] ?? '') }}" 
                  method="POST" 
                  class="needs-validation" 
                  novalidate>
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Invite to Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               name="email" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select @error('role') is-invalid @enderror" name="role" required>
                            <option value="STUDENT">Student</option>
                            <option value="TEACHER">Teacher</option>
                        </select>
                        @error('role')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Send Invitation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
function refreshData() {
    window.location.reload();
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
});
</script>
@endsection

@section('style')
<style>
.course-card {
    transition: transform 0.2s;
}
.course-card:hover {
    transform: translateY(-5px);
}
.table td {
    vertical-align: middle;
}
</style>
@endsection