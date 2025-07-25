<?php

namespace App\Http\Controllers;

use App\Services\GoogleClassroomService;
use App\Services\GoogleClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class GoogleClassroomController extends Controller
{
    protected GoogleClassroomService $classroomService;
    protected GoogleClientService $googleClientService;

    public function __construct(
        GoogleClassroomService $classroomService,
        GoogleClientService $googleClientService
    ) {
        $this->classroomService = $classroomService;
        $this->googleClientService = $googleClientService;
        $this->middleware('auth');
    }

    /**
     * Check if user has valid Google token
     */
    protected function checkGoogleConnection()
    {
        $user = Auth::user();
        
        if (!$this->googleClientService->hasValidToken($user)) {
            return redirect()->route('google.connect')
                ->with('error', 'Please connect your Google account to continue.');
        }
        
        return null;
    }

    // =================
    // COURSES ENDPOINTS
    // =================

    /**
     * Display all courses
     */
    public function index(Request $request): View|RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $user = Auth::user();
            $options = [];
            
            if ($request->has('pageToken')) {
                $options['pageToken'] = $request->get('pageToken');
            }
            
            if ($request->has('pageSize')) {
                $options['pageSize'] = min($request->get('pageSize'), 100);
            }
            
            if ($request->has('courseStates')) {
                $options['courseStates'] = $request->get('courseStates');
            }

            $result = $this->classroomService->getCourses($user, $options);

            return view('admin.classroom.index', [
                'courses' => $result['courses'] ?? [],
                'nextPageToken' => $result['nextPageToken'] ?? null,
                'currentPage' => $request->get('pageToken', ''),
                'courseStates' => $request->get('courseStates', [])
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getCourses:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to retrieve courses: ' . $e->getMessage());
        }
    }

    /**
     * Display a specific course
     */
    public function show(string $courseId): View|RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $user = Auth::user();
            $course = $this->classroomService->getCourse($user, $courseId);

            return view('admin.classroom.index', compact('course'));
        } catch (\Exception $e) {
            Log::error('Error in getCourse:', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('classroom.courses.index')
                ->with('error', 'Failed to retrieve course: ' . $e->getMessage());
        }
    }

    /**
     * Show form for creating a new course
     */
    public function create(): View|RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        return view('admin.classroom.index');
    }

    /**
     * Store a new course
     */
    public function store(Request $request): RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'section' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000',
                'room' => 'nullable|string|max:255'
            ]);

            $user = Auth::user();
            $course = $this->classroomService->createCourse($user, $validated);

            return redirect()->route('classroom.courses.show', $course['id'])
                ->with('success', 'Course created successfully!');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error in createCourse:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to create course: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form for editing a course
     */
    public function edit(string $courseId): View|RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $user = Auth::user();
            $course = $this->classroomService->getCourse($user, $courseId);

            return view('admin.classroom.index', compact('course'));
        } catch (\Exception $e) {
            Log::error('Error in edit course:', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('classroom.courses.index')
                ->with('error', 'Failed to load course for editing: ' . $e->getMessage());
        }
    }

    /**
     * Update a course
     */
    public function update(Request $request, string $courseId): RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'section' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000',
                'room' => 'nullable|string|max:255'
            ]);

            $user = Auth::user();
            $course = $this->classroomService->updateCourse($user, $courseId, $validated);

            return redirect()->route('classroom.courses.show', $courseId)
                ->with('success', 'Course updated successfully!');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error in updateCourse:', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update course: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete (archive) a course
     */
    public function destroy(string $courseId): RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $user = Auth::user();
            $this->classroomService->deleteCourse($user, $courseId);

            return redirect()->route('classroom.courses.index')
                ->with('success', 'Course archived successfully!');
        } catch (\Exception $e) {
            Log::error('Error in deleteCourse:', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to archive course: ' . $e->getMessage());
        }
    }

    // =====================
    // COURSEWORK ENDPOINTS
    // =====================

    /**
     * Display coursework for a course
     */
    public function coursework(Request $request, string $courseId): View|RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $user = Auth::user();
            $options = [];
            
            if ($request->has('pageToken')) {
                $options['pageToken'] = $request->get('pageToken');
            }
            
            if ($request->has('pageSize')) {
                $options['pageSize'] = min($request->get('pageSize'), 100);
            }
            
            if ($request->has('courseWorkStates')) {
                $options['courseWorkStates'] = $request->get('courseWorkStates');
            }

            $course = $this->classroomService->getCourse($user, $courseId);
            $result = $this->classroomService->getCourseWork($user, $courseId, $options);

            return view('admin.classroom.index', [
                'course' => $course,
                'coursework' => $result['courseWork'] ?? [],
                'nextPageToken' => $result['nextPageToken'] ?? null,
                'currentPage' => $request->get('pageToken', ''),
                'courseId' => $courseId
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getCourseWork:', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('classroom.courses.show', $courseId)
                ->with('error', 'Failed to retrieve coursework: ' . $e->getMessage());
        }
    }

    /**
     * Show form for creating coursework
     */
    public function createCoursework(string $courseId): View|RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $user = Auth::user();
            $course = $this->classroomService->getCourse($user, $courseId);

            return view('admin.classroom.index', compact('course'));
        } catch (\Exception $e) {
            return redirect()->route('classroom.courses.show', $courseId)
                ->with('error', 'Failed to load course: ' . $e->getMessage());
        }
    }

    /**
     * Store new coursework
     */
    public function storeCoursework(Request $request, string $courseId): RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string|max:5000',
                'workType' => 'nullable|string|in:ASSIGNMENT,SHORT_ANSWER_QUESTION,MULTIPLE_CHOICE_QUESTION',
                'dueDate' => 'nullable|date',
                'dueTime' => 'nullable|date_format:H:i',
                'maxPoints' => 'nullable|numeric|min:0|max:1000'
            ]);

            $user = Auth::user();
            $courseWork = $this->classroomService->createCourseWork($user, $courseId, $validated);

            return redirect()->route('classroom.coursework.index', $courseId)
                ->with('success', 'Assignment created successfully!');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error in createCourseWork:', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to create assignment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form for editing coursework
     */
    public function editCoursework(string $courseId, string $courseWorkId): View|RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $user = Auth::user();
            $course = $this->classroomService->getCourse($user, $courseId);
            // You'll need to add a method to get individual coursework
            $coursework = $this->classroomService->getCourseWorkItem($user, $courseId, $courseWorkId);

            return view('admin.classroom.index', compact('course', 'coursework'));
        } catch (\Exception $e) {
            return redirect()->route('classroom.coursework.index', $courseId)
                ->with('error', 'Failed to load assignment: ' . $e->getMessage());
        }
    }

    /**
     * Update coursework
     */
    public function updateCoursework(Request $request, string $courseId, string $courseWorkId): RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:5000',
                'dueDate' => 'nullable|date',
                'dueTime' => 'nullable|date_format:H:i',
                'maxPoints' => 'nullable|numeric|min:0|max:1000'
            ]);

            $user = Auth::user();
            $courseWork = $this->classroomService->updateCourseWork($user, $courseId, $courseWorkId, $validated);

            return redirect()->route('classroom.coursework.index', $courseId)
                ->with('success', 'Assignment updated successfully!');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error in updateCourseWork:', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'coursework_id' => $courseWorkId,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update assignment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Delete coursework
     */
    public function destroyCoursework(string $courseId, string $courseWorkId): RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $user = Auth::user();
            $this->classroomService->deleteCourseWork($user, $courseId, $courseWorkId);

            return redirect()->route('classroom.coursework.index', $courseId)
                ->with('success', 'Assignment deleted successfully!');
        } catch (\Exception $e) {
            Log::error('Error in deleteCourseWork:', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'coursework_id' => $courseWorkId,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete assignment: ' . $e->getMessage());
        }
    }

    // ==================
    // STUDENTS ENDPOINTS
    // ==================

    /**
     * Display students in a course
     */
    public function students(Request $request, string $courseId): View|RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $user = Auth::user();
            $options = [];
            
            if ($request->has('pageToken')) {
                $options['pageToken'] = $request->get('pageToken');
            }
            
            if ($request->has('pageSize')) {
                $options['pageSize'] = min($request->get('pageSize'), 100);
            }

            $course = $this->classroomService->getCourse($user, $courseId);
            $result = $this->classroomService->getStudents($user, $courseId, $options);

            return view('admin.classroom.index', [
                'course' => $course,
                'students' => $result['students'] ?? [],
                'nextPageToken' => $result['nextPageToken'] ?? null,
                'courseId' => $courseId
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getStudents:', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('classroom.courses.show', $courseId)
                ->with('error', 'Failed to retrieve students: ' . $e->getMessage());
        }
    }

    /**
     * Add student to course
     */
    public function addStudent(Request $request, string $courseId): RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $validated = $request->validate([
                'email' => 'required|email'
            ]);

            $user = Auth::user();
            $student = $this->classroomService->addStudent($user, $courseId, $validated['email']);

            return redirect()->route('classroom.students.index', $courseId)
                ->with('success', 'Student added successfully!');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error in addStudent:', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to add student: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove student from course
     */
    public function removeStudent(string $courseId, string $studentId): RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $user = Auth::user();
            $this->classroomService->removeStudent($user, $courseId, $studentId);

            return redirect()->route('classroom.students.index', $courseId)
                ->with('success', 'Student removed successfully!');
        } catch (\Exception $e) {
            Log::error('Error in removeStudent:', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to remove student: ' . $e->getMessage());
        }
    }

    // ==================
    // TEACHERS ENDPOINTS
    // ==================

    /**
     * Display teachers in a course
     */
    public function teachers(Request $request, string $courseId): View|RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $user = Auth::user();
            $options = [];
            
            if ($request->has('pageToken')) {
                $options['pageToken'] = $request->get('pageToken');
            }
            
            if ($request->has('pageSize')) {
                $options['pageSize'] = min($request->get('pageSize'), 100);
            }

            $course = $this->classroomService->getCourse($user, $courseId);
            $result = $this->classroomService->getTeachers($user, $courseId, $options);

            return view('admin.classroom.index', [
                'course' => $course,
                'teachers' => $result['teachers'] ?? [],
                'nextPageToken' => $result['nextPageToken'] ?? null,
                'courseId' => $courseId
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getTeachers:', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('classroom.courses.show', $courseId)
                ->with('error', 'Failed to retrieve teachers: ' . $e->getMessage());
        }
    }

    // ======================
    // SUBMISSIONS ENDPOINTS
    // ======================

    /**
     * Display submissions for coursework
     */
    public function submissions(Request $request, string $courseId, string $courseWorkId): View|RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $user = Auth::user();
            $options = [];
            
            if ($request->has('pageToken')) {
                $options['pageToken'] = $request->get('pageToken');
            }
            
            if ($request->has('pageSize')) {
                $options['pageSize'] = min($request->get('pageSize'), 100);
            }
            
            if ($request->has('states')) {
                $options['states'] = $request->get('states');
            }

            $course = $this->classroomService->getCourse($user, $courseId);
            $result = $this->classroomService->getSubmissions($user, $courseId, $courseWorkId, $options);

            return view('admin.classroom.index', [
                'course' => $course,
                'submissions' => $result['studentSubmissions'] ?? [],
                'nextPageToken' => $result['nextPageToken'] ?? null,
                'courseId' => $courseId,
                'courseWorkId' => $courseWorkId
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getSubmissions:', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'coursework_id' => $courseWorkId,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('classroom.coursework.index', $courseId)
                ->with('error', 'Failed to retrieve submissions: ' . $e->getMessage());
        }
    }

    /**
     * Grade a submission
     */
    public function gradeSubmission(Request $request, string $courseId, string $courseWorkId, string $submissionId): RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $validated = $request->validate([
                'grade' => 'required|numeric|min:0|max:1000'
            ]);

            $user = Auth::user();
            $submission = $this->classroomService->gradeSubmission(
                $user,
                $courseId,
                $courseWorkId,
                $submissionId,
                $validated['grade']
            );

            return redirect()->route('classroom.submissions.index', [$courseId, $courseWorkId])
                ->with('success', 'Submission graded successfully!');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error in gradeSubmission:', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'coursework_id' => $courseWorkId,
                'submission_id' => $submissionId,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to grade submission: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Return graded submissions to students
     */
    public function returnSubmissions(Request $request, string $courseId, string $courseWorkId): RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $validated = $request->validate([
                'submission_ids' => 'required|array',
                'submission_ids.*' => 'required|string'
            ]);

            $user = Auth::user();
            $submissions = $this->classroomService->returnSubmissions(
                $user,
                $courseId,
                $courseWorkId,
                $validated['submission_ids']
            );

            return redirect()->route('classroom.submissions.index', [$courseId, $courseWorkId])
                ->with('success', 'Submissions returned successfully!');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error in returnSubmissions:', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'coursework_id' => $courseWorkId,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to return submissions: ' . $e->getMessage());
        }
    }

    // ======================
    // INVITATIONS ENDPOINTS
    // ======================

    /**
     * Show form for creating invitation
     */
    public function createInvitationForm(string $courseId): View|RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $user = Auth::user();
            $course = $this->classroomService->getCourse($user, $courseId);

            return view('admin.classroom.index', compact('course'));
        } catch (\Exception $e) {
            return redirect()->route('classroom.courses.show', $courseId)
                ->with('error', 'Failed to load course: ' . $e->getMessage());
        }
    }

    /**
     * Create course invitation
     */
    public function storeInvitation(Request $request, string $courseId): RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'role' => 'required|string|in:STUDENT,TEACHER'
            ]);

            $user = Auth::user();
            $invitation = $this->classroomService->createInvitation(
                $user,
                $courseId,
                $validated['email'],
                $validated['role']
            );

            return redirect()->route('classroom.courses.show', $courseId)
                ->with('success', 'Invitation created successfully!');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Error in createInvitation:', [
                'user_id' => Auth::id(),
                'course_id' => $courseId,
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to create invitation: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display all invitations
     */
    public function invitations(Request $request): View|RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $user = Auth::user();
            $options = [];
            
            if ($request->has('pageToken')) {
                $options['pageToken'] = $request->get('pageToken');
            }
            
            if ($request->has('pageSize')) {
                $options['pageSize'] = min($request->get('pageSize'), 100);
            }
            
            if ($request->has('courseId')) {
                $options['courseId'] = $request->get('courseId');
            }

            $result = $this->classroomService->getInvitations($user, $options);

            return view('admin.classroom.index', [
                'invitations' => $result['invitations'] ?? [],
                'nextPageToken' => $result['nextPageToken'] ?? null,
                'currentPage' => $request->get('pageToken', ''),
                'filterCourseId' => $request->get('courseId', '')
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getInvitations:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('classroom.courses.index')
                ->with('error', 'Failed to retrieve invitations: ' . $e->getMessage());
        }
    }

    // =================
    // UTILITY ENDPOINTS
    // =================

    /**
     * Display connection status page
     */
    public function connectionStatus(): View
    {
        try {
            $user = Auth::user();
            $isConnected = $this->googleClientService->hasValidToken($user);

            return view('admin.classroom.index', [
                'connected' => $isConnected,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking connection status:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return view('admin.classroom.index', [
                'connected' => false,
                'user' => Auth::user(),
                'error' => 'Failed to check connection status: ' . $e->getMessage()
            ]);
        }
    }


    /**
     * Display user profile
     */
    public function profile(): View|RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $user = Auth::user();
            $this->classroomService->initializeService($user);
            
            // Get user profile using the Classroom service
            $client = $this->googleClientService->getClientForUser($user);
            $classroom = new \Google\Service\Classroom($client);
            $userProfile = $classroom->userProfiles->get('me');

            return view('admin.classroom.index', [
                'profile' => $userProfile,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting user profile:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect()->route('classroom.courses.index')
                ->with('error', 'Failed to get user profile: ' . $e->getMessage());
        }
    }

    /**
     * Display dashboard
     */
    public function dashboard(): View|RedirectResponse
    {
        $connectionCheck = $this->checkGoogleConnection();
        if ($connectionCheck) return $connectionCheck;

        try {
            $user = Auth::user();
            
            // Get overview data for dashboard
            $coursesResult = $this->classroomService->getCourses($user, ['pageSize' => 10]);
            $courses = $coursesResult['courses'] ?? [];
            
            // Get recent coursework from first few courses
            $recentCoursework = [];
            foreach (array_slice($courses, 0, 3) as $course) {
                try {
                    $courseworkResult = $this->classroomService->getCourseWork($user, $course['id'], ['pageSize' => 5]);
                    if (!empty($courseworkResult['courseWork'])) {
                        $recentCoursework = array_merge($recentCoursework, $courseworkResult['courseWork']);
                    }
                } catch (\Exception $e) {
                    // Skip this course if we can't get coursework
                    Log::warning('Could not get coursework for course in dashboard:', [
                        'course_id' => $course['id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Sort coursework by creation date (most recent first)
            usort($recentCoursework, function($a, $b) {
                return strtotime($b['creationTime'] ?? '0') - strtotime($a['creationTime'] ?? '0');
            });
            
            // Limit to 10 most recent
            $recentCoursework = array_slice($recentCoursework, 0, 10);

            return view('admin.classroom.index', [
                'courses' => $courses,
                'coursesCount' => count($courses),
                'recentCoursework' => $recentCoursework,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading dashboard:', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return view('admin.classroom.index', [
                'courses' => [],
                'coursesCount' => 0,
                'recentCoursework' => [],
                'user' => Auth::user(),
                'error' => 'Failed to load dashboard data: ' . $e->getMessage()
            ]);
        }
    }
}