@extends('admin.layouts.master')
@section('title', 'Google Classroom Dashboard')
@section('content')
<div class="container-fluid">
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">Google Services /</span> Classroom Dashboard
    </h4>
    
    <!-- Connection Status -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="ti ti-brand-google-classroom fs-3 me-2 {{ $isConnected ? 'text-success' : 'text-danger' }}"></i>
                    <div>
                        <h5 class="mb-0">Google Classroom</h5>
                        <small class="text-muted">{{ $isConnected ? 'Connected and Ready' : 'Not Connected' }}</small>
                    </div>
                </div>
                @if(!$isConnected)
                    <a href="{{ route('google.auth') }}" class="btn btn-primary">
                        <i class="ti ti-plug me-1"></i> Connect Account
                    </a>
                @endif
            </div>
        </div>
    </div>
    
    @if($isConnected)
        <!-- Dashboard Stats -->
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <i class="ti ti-books fs-3 me-2 text-primary"></i>
                                    <div>
                                        <div class="small text-muted">Total Courses</div>
                                        <h5 class="mb-0">{{ $coursesCount }}</h5>
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
                                    <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                        <i class="ti ti-refresh me-1"></i> Refresh
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Coursework -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Coursework</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Course</th>
                                    <th>Assignment</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentCoursework as $work)
                                    <tr>
                                        <td>{{ $work['courseName'] }}</td>
                                        <td>{{ $work['title'] }}</td>
                                        <td>{{ $work['dueDate'] ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $work['state'] === 'PUBLISHED' ? 'success' : 'secondary' }}">
                                                {{ $work['state'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>


 <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Courses List</h5>
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
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($courses as $course)
                        <tr>
                            <td>{{ $course['name'] }}</td>
                            <td>{{ $course['section'] ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-{{ $course['courseState'] === 'ACTIVE' ? 'success' : 'secondary' }}">
                                    {{ $course['courseState'] }}
                                </span>
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
                                        <a class="dropdown-item" href="{{ route('classroom.courses.edit', $course['id']) }}">
                                            <i class="ti ti-edit me-1"></i> Edit
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <form action="{{ route('classroom.courses.destroy', $course['id']) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Archive this course?')">
                                                <i class="ti ti-archive me-1"></i> Archive
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <!-- Pagination -->
            @if($nextPageToken)
                <div class="d-flex justify-content-center mt-3">
                    <a href="{{ route('classroom.courses.index', ['pageToken' => $nextPageToken]) }}" class="btn btn-outline-primary">
                        Load More
                    </a>
                </div>
            @endif
        </tbody>
    </table>
</div>



<!-- Create Coursework Modal -->
<div class="modal fade" id="createCourseworkModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('classroom.coursework.store', $courseId) }}" method="POST" class="needs-validation" novalidate>
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create New Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3"></textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="workType" class="form-select @error('workType') is-invalid @enderror" required>
                            <option value="ASSIGNMENT">Assignment</option>
                            <option value="SHORT_ANSWER_QUESTION">Short Answer</option>
                            <option value="MULTIPLE_CHOICE_QUESTION">Multiple Choice</option>
                        </select>
                        @error('workType') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="dueDate" class="form-control @error('dueDate') is-invalid @enderror">
                        @error('dueDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Max Points</label>
                        <input type="number" name="maxPoints" class="form-control @error('maxPoints') is-invalid @enderror">
                        @error('maxPoints') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>



<div class="container-fluid">
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">{{ $course['name'] }} / {{ $work['title'] }} /</span> Submissions
    </h4>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Student Submissions</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Submission Status</th>
                        <th>Grade</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($submissions as $submission)
                        <tr>
                            <td>{{ $submission['studentName'] }}</td>
                            <td>{{ $submission['state'] }}</td>
                            <td>{{ $submission['grade'] ?? 'N/A' }}</td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#gradeModal-{{ $submission['id'] }}">
                                    <i class="ti ti-check me-1"></i> Grade
                                </button>
                            </td>
                        </tr>
                        <!-- Grade Modal -->
                        <div class="modal fade" id="gradeModal-{{ $submission['id'] }}">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('classroom.submissions.grade', [$courseId, $workId, $submission['id']]) }}" method="POST">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Grade Submission</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Points</label>
                                                <input type="number" name="grade" min="0" max="1000" class="form-control @error('grade') is-invalid @enderror" required>
                                                @error('grade') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Submit Grade</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </tbody>
            </table>
            <!-- Pagination -->
            @if($nextPageToken)
                <div class="d-flex justify-content-center mt-3">
                    <a href="{{ route('classroom.submissions.index', [$courseId, $workId, 'pageToken' => $nextPageToken]) }}" class="btn btn-outline-primary">
                        Load More
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>








<div class="container-fluid">
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">{{ $course['name'] }} /</span> Students
    </h4>
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Students List</h5>
        </div>
        <div class="card-body">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                <i class="ti ti-plus me-1"></i> Add Student
            </button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Enrollment Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                        <tr>
                            <td>{{ $student['profile']['name']['fullName'] }}</td>
                            <td>{{ $student['profile']['emailAddress'] }}</td>
                            <td>{{ $student['creationTime'] }}</td>
                            <td>
                                <form action="{{ route('classroom.students.destroy', [$courseId, $student['userId']]) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this student?')">
                                        <i class="ti ti-trash me-1"></i> Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <!-- Pagination -->
            @if($nextPageToken)
                <div class="d-flex justify-content-center mt-3">
                    <a href="{{ route('classroom.students.index', ['courseId' => $courseId, 'pageToken' => $nextPageToken]) }}" class="btn btn-outline-primary">
                        Load More
                    </a>
                </div>
            @endif
        </div>
    </div>
    
    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('classroom.students.store', $courseId) }}" method="POST" class="needs-validation" novalidate>
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Student</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>




<div class="container-fluid">
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">Google Services /</span> Invitations
    </h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0">Invitations</h5>
            <div class="input-group mb-3">
                <input type="text" class="form-control" placeholder="Filter by course ID" aria-label="Filter by course ID" aria-describedby="filter">
                <button class="btn btn-outline-secondary" type="button" id="filter">Filter</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Course ID</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invitations as $invitation)
                        <tr>
                            <td>{{ $invitation['courseId'] }}</td>
                            <td>{{ $invitation['email'] }}</td>
                            <td>{{ $invitation['role'] }}</td>
                            <td>
                                <span class="badge bg-{{ $invitation['state'] === 'PENDING' ? 'warning' : 'success' }}">
                                    {{ $invitation['state'] }}
                                </span>
                            </td>
                            <td>{{ $invitation['creationTime'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <!-- Pagination -->
            @if($nextPageToken)
                <div class="d-flex justify-content-center mt-3">
                    <a href="{{ route('classroom.invitations.index', ['pageToken' => $nextPageToken]) }}" class="btn btn-outline-primary">
                        Load More
                    </a>
                </div>
            @endif
        </tbody>
    </table>
</div>


@endsection