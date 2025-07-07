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
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;


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



    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.auth');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
    
    Route::prefix('calendar')->group(function () {
        Route::get('/', [GoogleCalendarController::class, 'index'])->name('calendar.index');
        Route::post('/', [GoogleCalendarController::class, 'store'])->name('calendar.store');
        Route::put('/{eventId}', [GoogleCalendarController::class, 'update'])->name('calendar.update');
        Route::delete('/{eventId}', [GoogleCalendarController::class, 'destroy'])->name('calendar.destroy');
    });


});


Route::middleware(['auth', 'verified', '2fa'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
