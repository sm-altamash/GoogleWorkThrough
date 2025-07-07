<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\NadraImport;
use App\Models\NadraRecord;
use App\Models\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;
use Maatwebsite\Excel\Validators\ValidationException;
use Illuminate\Validation\Rule; 

class NadraController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {

            $data = NadraRecord::with('fileUpload')
                ->select([
                    'id', 'full_name', 'father_name', 'gender', 
                    'date_of_birth', 'cnic_number', 'family_id', 
                    'addresses', 'province', 'district', 'file_upload_id'
                ])
                ->orderBy('id', 'desc'); // Changed to DESC to show latest records first

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    // Using data attributes for easy JavaScript handling
                    $editBtn = '<button type="button" class="btn btn-sm btn-icon btn-outline-primary me-2 edit-btn" data-id="'.$row->id.'" data-bs-toggle="modal" data-bs-target="#editNadraModal">
                        <i class="ti ti-edit"></i>
                    </button>';
                    $deleteBtn = '<button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-btn" data-id="'.$row->id.'">
                        <i class="ti ti-trash"></i>
                    </button>';
                    return $editBtn . $deleteBtn;
                })
                ->addColumn('category', function($row){
                    // Safe null checking to prevent errors
                    return $row->fileUpload ? $row->fileUpload->category : 'Unknown';
                })
                ->editColumn('date_of_birth', function($row){
                    // Format date for display, handle null values
                    return $row->date_of_birth ? $row->date_of_birth->format('Y-m-d') : '-';
                })
                ->editColumn('addresses', function($row){
                    // Truncate long addresses for better table display
                    if (!$row->addresses) return '-';
                    return strlen($row->addresses) > 50 ? substr($row->addresses, 0, 50) . '...' : $row->addresses;
                })
                ->rawColumns(['action']) // Allow HTML in action column
                ->make(true);
        }

        // Return view for non-AJAX requests
        return view('admin.nadra.import');
    }

    public function import(Request $request)
    {
        // Comprehensive validation rules
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls,csv|max:10240', // Increased size limit to 10MB
            'category' => 'required|string|max:255'
        ]);

        // Using database transaction for data integrity
        \DB::beginTransaction();
        
        try {
            $file = $request->file('excel_file');
            $originalFilename = $file->getClientOriginalName();
            $storedFilename = time() . '_' . $originalFilename;

            $fileUpload = FileUpload::create([
                'original_filename' => $originalFilename,
                'stored_filename' => $storedFilename,
                'category' => $request->category,
                'total_records' => 0, // Initialize with 0
                'uploaded_at' => now()
            ]);

            // Debug: Log file upload creation
            \Log::info('FileUpload created', ['id' => $fileUpload->id, 'category' => $request->category]);


            $import = new NadraImport($fileUpload->id);
            Excel::import($import, $file);

            // Count successfully imported records
            $totalRecords = NadraRecord::where('file_upload_id', $fileUpload->id)->count();
            
            // Update the file upload record with actual count
            $fileUpload->update(['total_records' => $totalRecords]);

            // Commit transaction if everything successful
            \DB::commit();

            // Debug: Log successful import
            \Log::info('Import successful', ['total_records' => $totalRecords]);

            return redirect()->back()->with('success', 
                'Data imported successfully! Total records: ' . $totalRecords . 
                ' | File: ' . $originalFilename . 
                ' | Category: ' . $request->category
            );

        } catch (ValidationException $e) {

            
            $failures = $e->failures();
            $errorMessages = [];

            foreach ($failures as $failure) {
                $row = $failure->row();
                $errors = $failure->errors();
                $values = $failure->values();

                foreach ($errors as $error) {
                    $cnicNumber = isset($values['cnic_number']) ? $values['cnic_number'] : 'N/A';
                    $errorMessages[] = "Row {$row}: {$error} (CNIC: {$cnicNumber})";
                }
            }

            if (isset($fileUpload)) {
                $totalRecords = NadraRecord::where('file_upload_id', $fileUpload->id)->count();
                $fileUpload->update(['total_records' => $totalRecords]);
                
                // Commit partial success
                \DB::commit();
            }

            $errorMessage = "Import completed with errors. Successfully imported records: " . ($totalRecords ?? 0) . "\n\nErrors:\n" . implode("\n", array_slice($errorMessages, 0, 10));
            if (count($errorMessages) > 10) {
                $errorMessage .= "\n... and " . (count($errorMessages) - 10) . " more errors.";
            }

            return redirect()->back()->with('warning', $errorMessage);

        } catch (\Exception $e) {
            // Rollback transaction on any other error
            \DB::rollBack();
            
            // Clean up file upload record if it exists
            if (isset($fileUpload)) {
                $fileUpload->delete();
            }

            // Log the error for debugging
            \Log::error('Import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return redirect()->back()->with('error', 'Error importing data: ' . $e->getMessage());
        }
    }

    public function getUploadedFiles()
    {
        $files = FileUpload::with('nadraRecords')
            ->orderBy('uploaded_at', 'desc')
            ->get()
            ->map(function($file) {
                // Add computed fields for frontend
                $file->records_count = $file->nadraRecords->count();
                $file->formatted_date = $file->uploaded_at->format('Y-m-d H:i:s');
                return $file;
            });

        return response()->json($files);
    }

    public function getFileData($fileId)
    {
        try {
            $file = FileUpload::with(['nadraRecords' => function($query) {
                $query->orderBy('created_at', 'desc');
            }])->findOrFail($fileId);

            return response()->json([
                'success' => true,
                'file' => $file,
                'records' => $file->nadraRecords,
                'total_records' => $file->nadraRecords->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => 'File not found'
            ], 404);
        }
    }

    public function edit($id)
    {
        try {
            $record = NadraRecord::with('fileUpload')->findOrFail($id);
            
            // Format date for form input
            if ($record->date_of_birth) {
                $record->date_of_birth = $record->date_of_birth->format('Y-m-d');
            }
            
            return response()->json([
                'success' => true,
                'record' => $record
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => 'Record not found'
            ], 404);
        }
    }

    // FIXED UPDATE METHOD - This is the main fix for your update error
    public function update(Request $request, $id)
    {
        try {
            // First, find the record or fail
            $record = NadraRecord::findOrFail($id);
            
            // Log the incoming request for debugging
            \Log::info('Update request received', [
                'id' => $id,
                'data' => $request->all()
            ]);
            
            // Validation rules with context-aware uniqueness
            $validatedData = $request->validate([
                'full_name' => 'required|string|max:255',
                'father_name' => 'required|string|max:255',
                'gender' => 'required|in:Male,Female,Other',
                'date_of_birth' => 'required|date|before:today', // Must be in the past
                'cnic_number' => [
                    'required',
                    'string',
                    'regex:/^[0-9]{5}-[0-9]{7}-[0-9]$/', // CNIC format validation
                    Rule::unique('nadra_records', 'cnic_number')
                        ->where(function ($query) use ($record) {
                            return $query->where('file_upload_id', $record->file_upload_id);
                        })
                        ->ignore($id), // Ignore current record
                ],
                'family_id' => 'nullable|string|max:255',
                'addresses' => 'nullable|string|max:1000',
                'province' => 'nullable|string|max:255',
                'district' => 'nullable|string|max:255',
            ]);

            // Update record with validated data
            $record->update($validatedData);

            // Log successful update
            \Log::info('Record updated successfully', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Record updated successfully.',
                'record' => $record->fresh() // Return updated record
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation error
            \Log::error('Validation error in update', [
                'id' => $id,
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log model not found error
            \Log::error('Record not found in update', ['id' => $id]);
            
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => 'Record not found.'
            ], 404);
        } catch (\Exception $e) {
            // Log any other error
            \Log::error('General error in update', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => 'Error updating record: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $record = NadraRecord::findOrFail($id);
            $fileUploadId = $record->file_upload_id;
            
            $record->delete();

            // Update file upload record count
            if ($fileUploadId) {
                $fileUpload = FileUpload::find($fileUploadId);
                if ($fileUpload) {
                    $newCount = NadraRecord::where('file_upload_id', $fileUploadId)->count();
                    $fileUpload->update(['total_records' => $newCount]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Record deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => true,
                'message' => 'Error deleting record: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkDuplicateCnic(Request $request)
    {
        $cnic = $request->input('cnic');
        $excludeId = $request->input('exclude_id');
        $fileUploadId = $request->input('file_upload_id');

        if (!$cnic) {
            return response()->json([
                'valid' => false,
                'message' => 'CNIC number is required.'
            ]);
        }

        // Check CNIC format
        if (!preg_match('/^[0-9]{5}-[0-9]{7}-[0-9]$/', $cnic)) {
            return response()->json([
                'valid' => false,
                'message' => 'CNIC format is invalid. Use format: 12345-1234567-1'
            ]);
        }

        // Build query for uniqueness check
        $query = NadraRecord::where('cnic_number', $cnic);
        
        // If file upload ID is provided, check within that file only
        if ($fileUploadId) {
            $query->where('file_upload_id', $fileUploadId);
        }

        // Exclude current record if editing
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $exists = $query->exists();

        return response()->json([
            'valid' => !$exists,
            'exists' => $exists,
            'message' => $exists ? 'CNIC number already exists.' : 'CNIC number is available.'
        ]);
    }
}