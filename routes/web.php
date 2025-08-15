<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    DashboardController,
    NadraController,
    PermissionController,
    ProfileController,
    RoleController,
    Auth\TwoFactorController,
    UserController,
    GoogleAuthController,
    GoogleCalendarController,
    WhatsAppController,
    YouTubeController,
    GoogleGmailcontroller,
    GoogleDriveController,
    AIAgentController,
    Admin\AdminManagementController,
    GoogleClassroomController,
    GoogleFormsController
};

// Test and Redirect
Route::get('/', fn() => redirect('/login'));

// Auth routes
require __DIR__ . '/auth.php';

// WhatsApp webhook (open route)
Route::post('/whatsapp/webhook', [WhatsAppController::class, 'webhook']);

// Dashboard (with extra middleware)
Route::middleware(['auth', 'verified', '2fa'])->get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Protected Routes
Route::middleware('auth')->group(function () {

    // 2FA Routes
    Route::prefix('2fa')->name('2fa.')->group(function () {
        Route::get('/setup', [TwoFactorController::class, 'show2faForm'])->name('setup');
        Route::post('/verify', [TwoFactorController::class, 'verify2fa'])->name('verify');
        Route::post('/reset', [TwoFactorController::class, 'reset'])->name('reset');
        Route::post('/ajax-verify', [TwoFactorController::class, 'ajaxVerify'])->name('ajax-verify');
    });

    // Profile Routes
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // Resourceful Controllers
    Route::resources([
        'users' => UserController::class,
        'roles' => RoleController::class,
        'permissions' => PermissionController::class,
    ]);

    // NADRA
    Route::prefix('nadra')->name('nadra.')->group(function () {
        Route::get('/', [NadraController::class, 'index'])->name('index');
        Route::post('/import', [NadraController::class, 'import'])->name('import');
        Route::get('/edit/{id}', [NadraController::class, 'edit'])->name('edit');
        Route::post('/update/{id}', [NadraController::class, 'update'])->name('update');
        Route::post('/destroy/{id}', [NadraController::class, 'destroy'])->name('destroy');
        Route::get('/uploaded-files', [NadraController::class, 'getUploadedFiles'])->name('uploaded-files');
        Route::get('/file-data/{fileId}', [NadraController::class, 'getFileData'])->name('file-data');
        Route::post('/check-cnic', [NadraController::class, 'checkDuplicateCnic'])->name('check-cnic');
    });

    // Google Auth
    Route::prefix('auth/google')->name('google.')->group(function () {
        Route::get('/', [GoogleAuthController::class, 'redirect'])->name('auth');
        Route::get('/callback', [GoogleAuthController::class, 'callback'])->name('callback');
        Route::post('/disconnect', [GoogleAuthController::class, 'disconnect'])->name('disconnect');
    });

    // Calendar
    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::get('/check-connection', [GoogleCalendarController::class, 'checkConnection']);
        Route::get('/view', [GoogleCalendarController::class, 'view'])->name('view');
        Route::get('/', [GoogleCalendarController::class, 'index'])->name('index');
        Route::get('/{eventId}', [GoogleCalendarController::class, 'show'])->name('show');
        Route::post('/', [GoogleCalendarController::class, 'store'])->name('store');
        Route::put('/{eventId}', [GoogleCalendarController::class, 'update'])->name('update');
        Route::delete('/{eventId}', [GoogleCalendarController::class, 'destroy'])->name('destroy');
    });

    // YouTube
    Route::prefix('youtube')->name('youtube.')->group(function () {
        Route::get('/auth', [YouTubeController::class, 'auth'])->name('auth');
        Route::post('/disconnect', [YouTubeController::class, 'disconnect'])->name('disconnect');
        Route::get('/upload', [YouTubeController::class, 'index'])->name('upload.form');
        Route::post('/upload', [YouTubeController::class, 'upload'])->name('upload');
        Route::get('/dashboard', [YouTubeController::class, 'dashboard'])->name('dashboard');
        Route::get('/videos', [YouTubeController::class, 'videos'])->name('videos');
        Route::get('/videos/{videoId}', [YouTubeController::class, 'show'])->name('videos.show');
        Route::put('/videos/{videoId}', [YouTubeController::class, 'update'])->name('videos.update');
        Route::delete('/videos/{videoId}', [YouTubeController::class, 'destroy'])->name('videos.destroy');
        Route::get('/videos/{videoId}/analytics', [YouTubeController::class, 'analytics'])->name('videos.analytics');
        Route::get('/playlists', [YouTubeController::class, 'getPlaylists'])->name('playlists');
        Route::post('/playlists', [YouTubeController::class, 'createPlaylist'])->name('playlists.create');        
        Route::post('/videos/bulk-action', [YouTubeController::class, 'bulkAction'])->name('videos.bulk');
    });

    // Gmail
    Route::prefix('gmail')->name('gmail.')->group(function () {
        Route::get('/', [GoogleGmailcontroller::class, 'view'])->name('view');
        Route::get('/messages', [GoogleGmailcontroller::class, 'index'])->name('index');
        Route::post('/send', [GoogleGmailcontroller::class, 'send'])->name('send');
        Route::post('/draft', [GoogleGmailcontroller::class, 'createDraft'])->name('draft');
        Route::get('/drafts', [GoogleGmailcontroller::class, 'listDrafts'])->name('drafts');
        Route::delete('/drafts/{id}', [GoogleGmailcontroller::class, 'deleteDraft'])->name('drafts.delete');
    });

    // Google Drive
    Route::get('/drive', [GoogleDriveController::class, 'index'])->name('drive.index');
    Route::post('/drive/upload', [GoogleDriveController::class, 'upload'])->name('drive.upload');

    // WhatsApp
    Route::get('/whatsapp', [WhatsAppController::class, 'index'])->name('whatsapp.index');
    Route::get('/whatsapp/{number}', [WhatsAppController::class, 'show'])->name('whatsapp.show');
    Route::post('/whatsapp/send', [WhatsAppController::class, 'sendMessage'])->name('whatsapp.send');

    // AI Agent
    Route::prefix('ai-agent')->group(function () {
        Route::get('/', [AIAgentController::class, 'index'])->name('ai-agent.dashboard');
        Route::get('/health', [AIAgentController::class, 'healthCheck'])->name('ai-agent.health');
    });

    Route::prefix('api/ai-agent')->group(function () {
        Route::post('/summarize/url', [AIAgentController::class, 'summarizeUrl'])->name('ai-agent.summarize.url');
        Route::post('/question/url', [AIAgentController::class, 'askQuestionUrl'])->name('ai-agent.question.url');
        Route::post('/summarize/file', [AIAgentController::class, 'summarizeFile'])->name('ai-agent.summarize.file');
        Route::post('/question/file', [AIAgentController::class, 'askQuestionFile'])->name('ai-agent.question.file');
    });

    // Admin Management
    Route::prefix('admin-management')->group(function () {
        Route::post('/create-email', [AdminManagementController::class, 'createInstitutionalEmail']);
        Route::post('/create-from-module', [AdminManagementController::class, 'createEmailFromFirstModule']);
        Route::post('/webhook/create-email', [AdminManagementController::class, 'webhookCreateEmail']);
        Route::get('/email-status/{userId}', [AdminManagementController::class, 'getEmailStatus']);
    });

    // Google Classroom
    Route::prefix('classroom')->name('classroom.')->group(function () {
        // Courses
        Route::get('/courses', [GoogleClassroomController::class, 'index'])->name('courses.index');
        Route::get('/courses/create', [GoogleClassroomController::class, 'create'])->name('courses.create');
        Route::post('/courses', [GoogleClassroomController::class, 'store'])->name('courses.store');
        Route::get('/courses/{courseId}', [GoogleClassroomController::class, 'show'])->name('courses.show');
        Route::get('/courses/{courseId}/edit', [GoogleClassroomController::class, 'edit'])->name('courses.edit');
        Route::put('/courses/{courseId}', [GoogleClassroomController::class, 'update'])->name('courses.update');
        Route::delete('/courses/{courseId}', [GoogleClassroomController::class, 'destroy'])->name('courses.destroy');

        // Coursework
        Route::get('/courses/{courseId}/coursework', [GoogleClassroomController::class, 'coursework'])->name('coursework.index');
        Route::get('/courses/{courseId}/coursework/create', [GoogleClassroomController::class, 'createCoursework'])->name('coursework.create');
        Route::post('/courses/{courseId}/coursework', [GoogleClassroomController::class, 'storeCoursework'])->name('coursework.store');
        Route::get('/courses/{courseId}/coursework/{courseWorkId}/edit', [GoogleClassroomController::class, 'editCoursework'])->name('coursework.edit');
        Route::put('/courses/{courseId}/coursework/{courseWorkId}', [GoogleClassroomController::class, 'updateCoursework'])->name('coursework.update');
        Route::delete('/courses/{courseId}/coursework/{courseWorkId}', [GoogleClassroomController::class, 'destroyCoursework'])->name('coursework.destroy');

        // Students
        Route::get('/courses/{courseId}/students', [GoogleClassroomController::class, 'students'])->name('students.index');
        Route::post('/courses/{courseId}/students', [GoogleClassroomController::class, 'addStudent'])->name('students.store');
        Route::delete('/courses/{courseId}/students/{studentId}', [GoogleClassroomController::class, 'removeStudent'])->name('students.destroy');

        // Teachers
        Route::get('/courses/{courseId}/teachers', [GoogleClassroomController::class, 'teachers'])->name('teachers.index');

        // Submissions
        Route::get('/courses/{courseId}/coursework/{courseWorkId}/submissions', [GoogleClassroomController::class, 'submissions'])->name('submissions.index');
        Route::post('/courses/{courseId}/coursework/{courseWorkId}/submissions/{submissionId}/grade', [GoogleClassroomController::class, 'gradeSubmission'])->name('submissions.grade');
        Route::post('/courses/{courseId}/coursework/{courseWorkId}/return', [GoogleClassroomController::class, 'returnSubmissions'])->name('submissions.return');

        // Invitations
        Route::get('/courses/{courseId}/invitations/create', [GoogleClassroomController::class, 'createInvitationForm'])->name('invitations.create');
        Route::post('/courses/{courseId}/invitations', [GoogleClassroomController::class, 'storeInvitation'])->name('invitations.store');
        Route::get('/invitations', [GoogleClassroomController::class, 'invitations'])->name('invitations.index');

        // Utilities
        Route::get('/connection-status', [GoogleClassroomController::class, 'connectionStatus'])->name('connection.status');
        Route::get('/profile', [GoogleClassroomController::class, 'profile'])->name('profile');
        Route::get('/dashboard', [GoogleClassroomController::class, 'dashboard'])->name('dashboard');
    });

    // Google Forms
    Route::prefix('forms')->name('forms.')->group(function () {
        Route::get('/', [GoogleFormsController::class, 'index'])->name('index');
        Route::get('/create', [GoogleFormsController::class, 'create'])->name('create');
        Route::post('/', [GoogleFormsController::class, 'store'])->name('store');
        Route::get('/{formId}', [GoogleFormsController::class, 'show'])->name('show');
        Route::get('/{formId}/edit', [GoogleFormsController::class, 'edit'])->name('edit');
        Route::put('/{formId}', [GoogleFormsController::class, 'update'])->name('update');
        Route::delete('/{formId}', [GoogleFormsController::class, 'destroy'])->name('destroy');

        // Questions
        Route::post('/{formId}/questions/text', [GoogleFormsController::class, 'addTextQuestion'])->name('questions.text');
        Route::post('/{formId}/questions/choice', [GoogleFormsController::class, 'addMultipleChoiceQuestion'])->name('questions.choice');

        // Responses
        Route::get('/{formId}/responses', [GoogleFormsController::class, 'showResponses'])->name('responses.show');
        Route::get('/{formId}/responses/data', [GoogleFormsController::class, 'responses'])->name('responses.data');
    });

    // Forms connection status
    Route::get('/google/forms/status', [GoogleFormsController::class, 'connectionStatus'])->name('google.forms.status');

});