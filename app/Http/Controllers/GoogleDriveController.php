<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleDriveController extends Controller
{
    protected GoogleDriveService $driveService;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
    }

    /**
     * Display Google Drive files
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user || !$user->googleToken) {
            return redirect()->route('calendar.view')->with('error', 'Please connect your Google account first.');
        }

        try {
            $files = $this->driveService->listFiles($user);
            return view('admin.drive.index', compact('files'));
        } catch (\Exception $e) {
            Log::error('Drive file listing error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to load files from Google Drive.');
        }
    }

    /**
     * Upload a file to Google Drive
     */
    public function upload(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->googleToken) {
            return redirect()->back()->with('error', 'Google account not connected.');
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,png,docx|max:2048',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $name = $file->getClientOriginalName();

        try {
            $result = $this->driveService->uploadFile($user, $path, $name);
            return redirect()->back()
                ->with('success', 'File uploaded successfully!')
                ->with('file_link', $result['link']);
        } catch (\Exception $e) {
            Log::error('Drive upload error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to upload file to Google Drive.');
        }
    }
}