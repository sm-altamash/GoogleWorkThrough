<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NadraController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;


Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/', function () {
    return redirect('/login');
    }); 

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Roles
    Route::resource('roles',RoleController::class);
    Route::resource('permissions',PermissionController::class);
    Route::resource('users',UserController::class);


    // NADRA Routes
    Route::prefix('admin/nadra')->name('nadra.')->group(function () {
        Route::get('/', [NadraController::class, 'index'])->name('index');
        Route::post('/import', [NadraController::class, 'import'])->name('import');
        Route::get('/edit/{id}', [NadraController::class, 'edit'])->name('edit');
        
        Route::post('/update/{id}', [NadraController::class, 'update'])->name('update');
        
        Route::post('/destroy/{id}', [NadraController::class, 'destroy'])->name('destroy');

        Route::get('/uploaded-files', [NadraController::class, 'getUploadedFiles'])->name('uploaded-files');
        Route::get('/file-data/{fileId}', [NadraController::class, 'getFileData'])->name('file-data');
        
        Route::post('/check-cnic', [NadraController::class, 'checkDuplicateCnic'])->name('check-cnic');
    });
  
    
});

require __DIR__.'/auth.php';
