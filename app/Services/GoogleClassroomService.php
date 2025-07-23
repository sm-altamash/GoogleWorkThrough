<?php

namespace App\Services;

use Google\Service\Classroom;
use Google\Service\Classroom\Course;
use Google\Service\Classroom\CourseWork;
use Google\Service\Classroom\Student;
use Google\Service\Classroom\Teacher;
use Google\Service\Classroom\Invitation;
use Google\Service\Classroom\StudentSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoogleClassroomService
{
    protected GoogleClientService $googleClientService;
    protected Classroom $classroomService;

    public function __construct(GoogleClientService $googleClientService)
    {
        $this->googleClientService = $googleClientService;
    }

    /**
     * Initialize the Classroom service for a user
     */
    protected function initializeService(User $user): void
    {
        $client = $this->googleClientService->getClientForUser($user);
        $this->classroomService = new Classroom($client);
    }

    /**
     * Get all courses for the authenticated user
     */
    public function getCourses(User $user, array $options = []): array
    {
        try {
            $this->initializeService($user);
            
            $params = array_merge([
                'pageSize' => 100,
                'courseStates' => ['ACTIVE']
            ], $options);

            $response = $this->classroomService->courses->listCourses($params);
            
            Log::info('Retrieved courses for user:', [
                'user_id' => $user->id,
                'course_count' => count($response->getCourses() ?? [])
            ]);

            return [
                'courses' => $response->getCourses() ?? [],
                'nextPageToken' => $response->getNextPageToken()
            ];
        } catch (\Exception $e) {
            Log::error('Error retrieving courses:', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to retrieve courses: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific course by ID
     */
    public function getCourse(User $user, string $courseId): Course
    {
        try {
            $this->initializeService($user);
            
            $course = $this->classroomService->courses->get($courseId);
            
            Log::info('Retrieved course:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'course_name' => $course->getName()
            ]);

            return $course;
        } catch (\Exception $e) {
            Log::error('Error retrieving course:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to retrieve course: ' . $e->getMessage());
        }
    }

    /**
     * Create a new course
     */
    public function createCourse(User $user, array $courseData): Course
    {
        try {
            $this->initializeService($user);
            
            $course = new Course();
            $course->setName($courseData['name']);
            $course->setSection($courseData['section'] ?? '');
            $course->setDescription($courseData['description'] ?? '');
            $course->setRoom($courseData['room'] ?? '');
            $course->setOwnerId('me');
            $course->setCourseState('ACTIVE');

            $createdCourse = $this->classroomService->courses->create($course);
            
            Log::info('Created course:', [
                'user_id' => $user->id,
                'course_id' => $createdCourse->getId(),
                'course_name' => $createdCourse->getName()
            ]);

            return $createdCourse;
        } catch (\Exception $e) {
            Log::error('Error creating course:', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'course_data' => $courseData
            ]);
            throw new \Exception('Failed to create course: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing course
     */
    public function updateCourse(User $user, string $courseId, array $courseData): Course
    {
        try {
            $this->initializeService($user);
            
            $course = $this->classroomService->courses->get($courseId);
            
            if (isset($courseData['name'])) $course->setName($courseData['name']);
            if (isset($courseData['section'])) $course->setSection($courseData['section']);
            if (isset($courseData['description'])) $course->setDescription($courseData['description']);
            if (isset($courseData['room'])) $course->setRoom($courseData['room']);

            $updatedCourse = $this->classroomService->courses->update($courseId, $course);
            
            Log::info('Updated course:', [
                'user_id' => $user->id,
                'course_id' => $courseId
            ]);

            return $updatedCourse;
        } catch (\Exception $e) {
            Log::error('Error updating course:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to update course: ' . $e->getMessage());
        }
    }

    /**
     * Delete a course (archive it)
     */
    public function deleteCourse(User $user, string $courseId): Course
    {
        try {
            $this->initializeService($user);
            
            $course = $this->classroomService->courses->get($courseId);
            $course->setCourseState('ARCHIVED');
            
            $archivedCourse = $this->classroomService->courses->update($courseId, $course);
            
            Log::info('Archived course:', [
                'user_id' => $user->id,
                'course_id' => $courseId
            ]);

            return $archivedCourse;
        } catch (\Exception $e) {
            Log::error('Error archiving course:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to archive course: ' . $e->getMessage());
        }
    }

    /**
     * Get course assignments (coursework)
     */
    public function getCourseWork(User $user, string $courseId, array $options = []): array
    {
        try {
            $this->initializeService($user);
            
            $params = array_merge([
                'pageSize' => 100,
                'courseWorkStates' => ['PUBLISHED']
            ], $options);

            $response = $this->classroomService->courses_courseWork->listCoursesCourseWork($courseId, $params);
            
            Log::info('Retrieved coursework for course:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'coursework_count' => count($response->getCourseWork() ?? [])
            ]);

            return [
                'courseWork' => $response->getCourseWork() ?? [],
                'nextPageToken' => $response->getNextPageToken()
            ];
        } catch (\Exception $e) {
            Log::error('Error retrieving coursework:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to retrieve coursework: ' . $e->getMessage());
        }
    }

    /**
     * Create new coursework (assignment)
     */
    public function createCourseWork(User $user, string $courseId, array $workData): CourseWork
    {
        try {
            $this->initializeService($user);
            
            $courseWork = new CourseWork();
            $courseWork->setTitle($workData['title']);
            $courseWork->setDescription($workData['description'] ?? '');
            $courseWork->setWorkType($workData['workType'] ?? 'ASSIGNMENT');
            $courseWork->setState('PUBLISHED');
            
            // Set due date if provided
            if (isset($workData['dueDate'])) {
                $dueDate = Carbon::parse($workData['dueDate']);
                $courseWork->setDueDate([
                    'year' => $dueDate->year,
                    'month' => $dueDate->month,
                    'day' => $dueDate->day
                ]);
                
                if (isset($workData['dueTime'])) {
                    $dueTime = Carbon::parse($workData['dueTime']);
                    $courseWork->setDueTime([
                        'hours' => $dueTime->hour,
                        'minutes' => $dueTime->minute
                    ]);
                }
            }
            
            // Set max points if provided
            if (isset($workData['maxPoints'])) {
                $courseWork->setMaxPoints($workData['maxPoints']);
            }

            $createdWork = $this->classroomService->courses_courseWork->create($courseId, $courseWork);
            
            Log::info('Created coursework:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'coursework_id' => $createdWork->getId(),
                'title' => $createdWork->getTitle()
            ]);

            return $createdWork;
        } catch (\Exception $e) {
            Log::error('Error creating coursework:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'error' => $e->getMessage(),
                'work_data' => $workData
            ]);
            throw new \Exception('Failed to create coursework: ' . $e->getMessage());
        }
    }

    /**
     * Update coursework
     */
    public function updateCourseWork(User $user, string $courseId, string $courseWorkId, array $workData): CourseWork
    {
        try {
            $this->initializeService($user);
            
            $courseWork = $this->classroomService->courses_courseWork->get($courseId, $courseWorkId);
            
            if (isset($workData['title'])) $courseWork->setTitle($workData['title']);
            if (isset($workData['description'])) $courseWork->setDescription($workData['description']);
            if (isset($workData['maxPoints'])) $courseWork->setMaxPoints($workData['maxPoints']);
            
            // Update due date if provided
            if (isset($workData['dueDate'])) {
                $dueDate = Carbon::parse($workData['dueDate']);
                $courseWork->setDueDate([
                    'year' => $dueDate->year,
                    'month' => $dueDate->month,
                    'day' => $dueDate->day
                ]);
            }

            $updatedWork = $this->classroomService->courses_courseWork->patch($courseId, $courseWorkId, $courseWork);
            
            Log::info('Updated coursework:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'coursework_id' => $courseWorkId
            ]);

            return $updatedWork;
        } catch (\Exception $e) {
            Log::error('Error updating coursework:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'coursework_id' => $courseWorkId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to update coursework: ' . $e->getMessage());
        }
    }

    /**
     * Delete coursework
     */
    public function deleteCourseWork(User $user, string $courseId, string $courseWorkId): bool
    {
        try {
            $this->initializeService($user);
            
            $this->classroomService->courses_courseWork->delete($courseId, $courseWorkId);
            
            Log::info('Deleted coursework:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'coursework_id' => $courseWorkId
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error deleting coursework:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'coursework_id' => $courseWorkId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to delete coursework: ' . $e->getMessage());
        }
    }

    /**
     * Get students in a course
     */
    public function getStudents(User $user, string $courseId, array $options = []): array
    {
        try {
            $this->initializeService($user);
            
            $params = array_merge([
                'pageSize' => 100
            ], $options);

            $response = $this->classroomService->courses_students->listCoursesStudents($courseId, $params);
            
            Log::info('Retrieved students for course:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'student_count' => count($response->getStudents() ?? [])
            ]);

            return [
                'students' => $response->getStudents() ?? [],
                'nextPageToken' => $response->getNextPageToken()
            ];
        } catch (\Exception $e) {
            Log::error('Error retrieving students:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to retrieve students: ' . $e->getMessage());
        }
    }

    /**
     * Add student to course
     */
    public function addStudent(User $user, string $courseId, string $studentEmail): Student
    {
        try {
            $this->initializeService($user);
            
            $student = new Student();
            $student->setUserId($studentEmail);
            
            $addedStudent = $this->classroomService->courses_students->create($courseId, $student);
            
            Log::info('Added student to course:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'student_email' => $studentEmail
            ]);

            return $addedStudent;
        } catch (\Exception $e) {
            Log::error('Error adding student:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'student_email' => $studentEmail,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to add student: ' . $e->getMessage());
        }
    }

    /**
     * Remove student from course
     */
    public function removeStudent(User $user, string $courseId, string $studentId): bool
    {
        try {
            $this->initializeService($user);
            
            $this->classroomService->courses_students->delete($courseId, $studentId);
            
            Log::info('Removed student from course:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'student_id' => $studentId
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error removing student:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to remove student: ' . $e->getMessage());
        }
    }

    /**
     * Get teachers in a course
     */
    public function getTeachers(User $user, string $courseId, array $options = []): array
    {
        try {
            $this->initializeService($user);
            
            $params = array_merge([
                'pageSize' => 100
            ], $options);

            $response = $this->classroomService->courses_teachers->listCoursesTeachers($courseId, $params);
            
            Log::info('Retrieved teachers for course:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'teacher_count' => count($response->getTeachers() ?? [])
            ]);

            return [
                'teachers' => $response->getTeachers() ?? [],
                'nextPageToken' => $response->getNextPageToken()
            ];
        } catch (\Exception $e) {
            Log::error('Error retrieving teachers:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to retrieve teachers: ' . $e->getMessage());
        }
    }

    /**
     * Get student submissions for coursework
     */
    public function getSubmissions(User $user, string $courseId, string $courseWorkId, array $options = []): array
    {
        try {
            $this->initializeService($user);
            
            $params = array_merge([
                'pageSize' => 100
            ], $options);

            $response = $this->classroomService->courses_courseWork_studentSubmissions
                ->listCoursesCourseWorkStudentSubmissions($courseId, $courseWorkId, $params);
            
            Log::info('Retrieved submissions for coursework:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'coursework_id' => $courseWorkId,
                'submission_count' => count($response->getStudentSubmissions() ?? [])
            ]);

            return [
                'submissions' => $response->getStudentSubmissions() ?? [],
                'nextPageToken' => $response->getNextPageToken()
            ];
        } catch (\Exception $e) {
            Log::error('Error retrieving submissions:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'coursework_id' => $courseWorkId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to retrieve submissions: ' . $e->getMessage());
        }
    }

    /**
     * Grade a student submission
     */
    public function gradeSubmission(User $user, string $courseId, string $courseWorkId, string $submissionId, float $grade): StudentSubmission
    {
        try {
            $this->initializeService($user);
            
            $submission = new StudentSubmission();
            $submission->setAssignedGrade($grade);
            $submission->setDraftGrade($grade);
            
            $gradedSubmission = $this->classroomService->courses_courseWork_studentSubmissions
                ->patch($courseId, $courseWorkId, $submissionId, $submission, [
                    'updateMask' => 'assignedGrade,draftGrade'
                ]);
            
            Log::info('Graded submission:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'coursework_id' => $courseWorkId,
                'submission_id' => $submissionId,
                'grade' => $grade
            ]);

            return $gradedSubmission;
        } catch (\Exception $e) {
            Log::error('Error grading submission:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'coursework_id' => $courseWorkId,
                'submission_id' => $submissionId,
                'grade' => $grade,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to grade submission: ' . $e->getMessage());
        }
    }

    /**
     * Return graded submissions to students
     */
    public function returnSubmissions(User $user, string $courseId, string $courseWorkId, array $submissionIds): array
    {
        try {
            $this->initializeService($user);
            
            $returnedSubmissions = [];
            
            foreach ($submissionIds as $submissionId) {
                $returnedSubmission = $this->classroomService->courses_courseWork_studentSubmissions
                    ->returnCoursesCourseWorkStudentSubmissions($courseId, $courseWorkId, $submissionId);
                
                $returnedSubmissions[] = $returnedSubmission;
            }
            
            Log::info('Returned submissions:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'coursework_id' => $courseWorkId,
                'submission_count' => count($submissionIds)
            ]);

            return $returnedSubmissions;
        } catch (\Exception $e) {
            Log::error('Error returning submissions:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'coursework_id' => $courseWorkId,
                'submission_ids' => $submissionIds,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to return submissions: ' . $e->getMessage());
        }
    }

    /**
     * Create course invitation
     */
    public function createInvitation(User $user, string $courseId, string $userEmail, string $role = 'STUDENT'): Invitation
    {
        try {
            $this->initializeService($user);
            
            $invitation = new Invitation();
            $invitation->setCourseId($courseId);
            $invitation->setUserId($userEmail);
            $invitation->setRole($role); // 'STUDENT' or 'TEACHER'
            
            $createdInvitation = $this->classroomService->invitations->create($invitation);
            
            Log::info('Created invitation:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'invited_email' => $userEmail,
                'role' => $role,
                'invitation_id' => $createdInvitation->getId()
            ]);

            return $createdInvitation;
        } catch (\Exception $e) {
            Log::error('Error creating invitation:', [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'invited_email' => $userEmail,
                'role' => $role,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to create invitation: ' . $e->getMessage());
        }
    }

    /**
     * Get course invitations
     */
    public function getInvitations(User $user, array $options = []): array
    {
        try {
            $this->initializeService($user);
            
            $params = array_merge([
                'pageSize' => 100
            ], $options);

            $response = $this->classroomService->invitations->listInvitations($params);
            
            Log::info('Retrieved invitations:', [
                'user_id' => $user->id,
                'invitation_count' => count($response->getInvitations() ?? [])
            ]);

            return [
                'invitations' => $response->getInvitations() ?? [],
                'nextPageToken' => $response->getNextPageToken()
            ];
        } catch (\Exception $e) {
            Log::error('Error retrieving invitations:', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to retrieve invitations: ' . $e->getMessage());
        }
    }
}