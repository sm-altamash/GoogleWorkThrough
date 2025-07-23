<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NadraController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\GoogleCalendarController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\YouTubeController;
use App\Http\Controllers\GoogleGmailcontroller;
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\AIAgentController;
use App\Http\Controllers\Admin\AdminManagementController;
use App\Http\Controllers\GoogleClassroomController;
use Illuminate\Support\Facades\Route;


Route::get('/test', function () {
    return view('admin.classroom.test');
});


Route::get('/', function () {
    return redirect('/login');
});

require __DIR__ . '/auth.php';


Route::middleware('auth')->group(function () {
    

    Route::prefix('2fa')->name('2fa.')->group(function () {
        Route::get('/setup', [TwoFactorController::class, 'show2faForm'])->name('setup');
        Route::post('/verify', [TwoFactorController::class, 'verify2fa'])->name('verify');
        Route::post('/reset', [TwoFactorController::class, 'reset'])->name('reset');
        Route::post('/ajax-verify', [TwoFactorController::class, 'ajaxVerify'])->name('ajax-verify');
    });


    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

  
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);

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

    Route::prefix('auth/google')->name('google.')->group(function () {
        Route::get('/', [GoogleAuthController::class, 'redirect'])->name('auth');
        Route::get('/callback', [GoogleAuthController::class, 'callback'])->name('callback');
        Route::post('/disconnect', [GoogleAuthController::class, 'disconnect'])->name('disconnect');
    });

    Route::prefix('calendar')->name('calendar.')->group(function () {
        Route::get('/check-connection', [GoogleCalendarController::class, 'checkConnection']);
        Route::get('/view', [GoogleCalendarController::class, 'view'])->name('view');
        Route::get('/', [GoogleCalendarController::class, 'index'])->name('index');
        Route::get('/{eventId}', [GoogleCalendarController::class, 'show'])->name('show');
        Route::post('/', [GoogleCalendarController::class, 'store'])->name('store');
        Route::put('/{eventId}', [GoogleCalendarController::class, 'update'])->name('update');
        Route::delete('/{eventId}', [GoogleCalendarController::class, 'destroy'])->name('destroy');
    });


    Route::prefix('youtube')->name('youtube.')->group(function () {
        Route::get('/upload', [YouTubeController::class, 'index'])->name('upload.form');
        Route::post('/upload', [YouTubeController::class, 'upload'])->name('upload');
        
        Route::get('/auth', [YouTubeController::class, 'auth'])->name('auth');
        Route::get('/videos', [YouTubeController::class, 'videos'])->name('videos');
        Route::get('/video/{videoId}', [YouTubeController::class, 'show'])->name('video.show');
        Route::put('/video/{videoId}', [YouTubeController::class, 'update'])->name('video.update');
        Route::delete('/video/{videoId}', [YouTubeController::class, 'destroy'])->name('video.destroy');
        Route::get('/dashboard', [YouTubeController::class, 'dashboard'])->name('dashboard');
        
        Route::get('/playlists', [YouTubeController::class, 'getPlaylists'])->name('playlists.ajax');
        Route::post('/playlists/create', [YouTubeController::class, 'createPlaylist'])->name('playlist.create');
        Route::get('/analytics/{videoId}', [YouTubeController::class, 'analytics'])->name('analytics');
        Route::post('/bulk-action', [YouTubeController::class, 'bulkAction'])->name('bulk.action');
    });


    Route::prefix('gmail')->name('gmail.')->group(function () {

        Route::get('/', [GoogleGmailController::class, 'view'])->name('view');

        Route::get('/messages', [GoogleGmailController::class, 'index'])->name('index');
        Route::post('/send', [GoogleGmailController::class, 'send'])->name('send');
        Route::post('/draft', [GoogleGmailController::class, 'createDraft'])->name('draft');

        Route::get('/drafts', [GoogleGmailController::class, 'listDrafts'])->name('drafts');
        Route::delete('/drafts/{id}', [GoogleGmailController::class, 'deleteDraft'])->name('drafts.delete');
    });


    Route::get('/drive', [GoogleDriveController::class, 'index'])->name('drive.index');
    Route::post('/drive/upload', [GoogleDriveController::class, 'upload'])->name('drive.upload');

    
    Route::get('/whatsapp', [WhatsAppController::class, 'index'])->name('whatsapp.index');
    Route::get('/whatsapp/{number}', [WhatsAppController::class, 'show'])->name('whatsapp.show');
    Route::post('/whatsapp/send', [WhatsAppController::class, 'sendMessage'])->name('whatsapp.send');


    Route::group(['prefix' => 'ai-agent'], function () {
        Route::get('/', [AIAgentController::class, 'index'])->name('ai-agent.dashboard');
        Route::get('/health', [AIAgentController::class, 'healthCheck'])->name('ai-agent.health');
    });

    Route::group(['prefix' => 'api/ai-agent'], function () {
        Route::post('/summarize/url', [AIAgentController::class, 'summarizeUrl'])->name('ai-agent.summarize.url');
        Route::post('/question/url', [AIAgentController::class, 'askQuestionUrl'])->name('ai-agent.question.url');
        Route::post('/summarize/file', [AIAgentController::class, 'summarizeFile'])->name('ai-agent.summarize.file');
        Route::post('/question/file', [AIAgentController::class, 'askQuestionFile'])->name('ai-agent.question.file');
    });



    Route::prefix('admin-management')->group(function () {
        Route::post('/create-email', [AdminManagementController::class, 'createInstitutionalEmail']);
        Route::post('/create-from-module', [AdminManagementController::class, 'createEmailFromFirstModule']);
        Route::post('/webhook/create-email', [AdminManagementController::class, 'webhookCreateEmail']);
        Route::get('/email-status/{userId}', [AdminManagementController::class, 'getEmailStatus']);
    });



    Route::prefix('classroom')->name('classroom.')->group(function () {
        // C O U R S E S
        Route::get('/courses', [GoogleClassroomController::class, 'index'])
            ->name('courses.index');
        Route::get('/courses/create', [GoogleClassroomController::class, 'create'])
            ->name('courses.create');
        Route::post('/courses', [GoogleClassroomController::class, 'store'])
            ->name('courses.store');
        Route::get('/courses/{courseId}', [GoogleClassroomController::class, 'show'])
            ->name('courses.show');
        Route::get('/courses/{courseId}/edit', [GoogleClassroomController::class, 'edit'])
            ->name('courses.edit');
        Route::put('/courses/{courseId}', [GoogleClassroomController::class, 'update'])
            ->name('courses.update');
        Route::delete('/courses/{courseId}', [GoogleClassroomController::class, 'destroy'])
            ->name('courses.destroy');

        // C O U R S E W O R K
        Route::get('/courses/{courseId}/coursework', [GoogleClassroomController::class, 'coursework'])
            ->name('coursework.index');
        Route::get('/courses/{courseId}/coursework/create', [GoogleClassroomController::class, 'createCoursework'])
            ->name('coursework.create');
        Route::post('/courses/{courseId}/coursework', [GoogleClassroomController::class, 'storeCoursework'])
            ->name('coursework.store');
        Route::get('/courses/{courseId}/coursework/{courseWorkId}/edit', [GoogleClassroomController::class, 'editCoursework'])
            ->name('coursework.edit');
        Route::put('/courses/{courseId}/coursework/{courseWorkId}', [GoogleClassroomController::class, 'updateCoursework'])
            ->name('coursework.update');
        Route::delete('/courses/{courseId}/coursework/{courseWorkId}', [GoogleClassroomController::class, 'destroyCoursework'])
            ->name('coursework.destroy');

        // S T U D E N T S
        Route::get('/courses/{courseId}/students', [GoogleClassroomController::class, 'students'])
            ->name('students.index');
        Route::post('/courses/{courseId}/students', [GoogleClassroomController::class, 'addStudent'])
            ->name('students.store');
        Route::delete('/courses/{courseId}/students/{studentId}', [GoogleClassroomController::class, 'removeStudent'])
            ->name('students.destroy');

        // T E A C H E R S
        Route::get('/courses/{courseId}/teachers', [GoogleClassroomController::class, 'teachers'])
            ->name('teachers.index');

        // S U B M I S S I O N S
        Route::get('/courses/{courseId}/coursework/{courseWorkId}/submissions', [GoogleClassroomController::class, 'submissions'])
            ->name('submissions.index');
        Route::post('/courses/{courseId}/coursework/{courseWorkId}/submissions/{submissionId}/grade', [GoogleClassroomController::class, 'gradeSubmission'])
            ->name('submissions.grade');
        Route::post('/courses/{courseId}/coursework/{courseWorkId}/return', [GoogleClassroomController::class, 'returnSubmissions'])
            ->name('submissions.return');

        // I N V I T A T I O N S
        Route::get('/courses/{courseId}/invitations/create', [GoogleClassroomController::class, 'createInvitationForm'])
            ->name('invitations.create');
        Route::post('/courses/{courseId}/invitations', [GoogleClassroomController::class, 'storeInvitation'])
            ->name('invitations.store');
        Route::get('/invitations', [GoogleClassroomController::class, 'invitations'])
            ->name('invitations.index');

        // U T I L I T I E S
        Route::get('/connection-status', [GoogleClassroomController::class, 'connectionStatus'])
            ->name('connection.status');
        Route::get('/profile', [GoogleClassroomController::class, 'profile'])
            ->name('profile');
        Route::get('/dashboard', [GoogleClassroomController::class, 'dashboard'])
            ->name('dashboard');
    });
});



Route::post('/whatsapp/webhook', [WhatsAppController::class, 'webhook']);

Route::middleware(['auth', 'verified', '2fa'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});