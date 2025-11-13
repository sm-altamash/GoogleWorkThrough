<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WorkspaveApiController;
use App\Http\Controllers\Api\BackupController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('google-workspace')->group(function () {
    
    Route::get('/health', [WorkspaveApiController::class, 'healthCheck'])->name('google-workspace.health');
    Route::prefix('users')->group(function () {
        Route::post('/', [WorkspaveApiController::class, 'createUser'])->name('google-workspace.users.create');
        Route::post('/bulk', [WorkspaveApiController::class, 'createBulkUsers'])->name('google-workspace.users.bulk-create');
        Route::get('/{email}', [WorkspaveApiController::class, 'getUser'])->name('google-workspace.users.show')->where('email', '.*'); 
        Route::put('/{email}', [WorkspaveApiController::class, 'updateUser'])->name('google-workspace.users.update')->where('email', '.*');           
        Route::patch('/{email}', [WorkspaveApiController::class, 'updateUser'])->name('google-workspace.users.patch')->where('email', '.*');
        Route::delete('/{email}', [WorkspaveApiController::class, 'deleteUser'])->name('google-workspace.users.delete')->where('email', '.*');
    });
});


Route::prefix('backup')->middleware('api.key')->group(function () {
    Route::post('/create', [BackupController::class, 'createAndUpload']);
    Route::get('/history', [BackupController::class, 'history']);
});


# Backup API Configuration
// BACKUP_API_KEY=your-secret-api-key-here-change-this
// Update .env File
// Add these to your .env:




