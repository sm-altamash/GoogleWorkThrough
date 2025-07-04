<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\NadraImport;
use App\Models\NadraRecord;
use App\Models\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;
use Maatwebsite\Excel\Validators\ValidationException;

class NadraController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = NadraRecord::with('fileUpload')
                ->select(['id', 'full_name', 'father_name', 'gender', 'date_of_birth', 'cnic_number', 'family_id', 'addresses', 'province', 'district', 'file_upload_id'])
                ->orderBy('id', 'asc'); // Add consistent ordering

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $editBtn = '<button type="button" class="btn btn-sm btn-icon btn-outline-primary me-2 edit-btn" data-id="'.$row->id.'" data-bs-toggle="modal" data-bs-target="#editNadraModal"><i class="ti ti-edit"></i></button>';
                    $deleteBtn = '<button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-btn" data-id="'.$row->id.'"><i class="ti ti-trash"></i></button>';
                    return $editBtn . $deleteBtn;
                })
                ->addColumn('category', function($row){
                    return $row->fileUpload ? $row->fileUpload->category : '2025';
                })
                ->editColumn('date_of_birth', function($row){
                    return $row->date_of_birth ? date('Y-m-d', strtotime($row->date_of_birth)) : '-';
                })
                ->editColumn('addresses', function($row){
                    return $row->addresses ? (strlen($row->addresses) > 50 ? substr($row->addresses, 0, 50) . '...' : $row->addresses) : '-';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.nadra.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|mimes:xlsx,xls|max:2048',
            'category' => 'required|string|max:255'
        ]);

        try {
            $file = $request->file('excel_file');
            $originalFilename = $file->getClientOriginalName();
            $storedFilename = time() . '_' . $originalFilename;

            // Store file info first
            $fileUpload = FileUpload::create([
                'original_filename' => $originalFilename,
                'stored_filename' => $storedFilename,
                'category' => $request->category,
                'uploaded_at' => now()
            ]);

            // Import with file upload ID
            Excel::import(new NadraImport($fileUpload->id), $file);

            // Update total records count
            $totalRecords = NadraRecord::where('file_upload_id', $fileUpload->id)->count();
            $fileUpload->update(['total_records' => $totalRecords]);

            return redirect()->back()->with('success', 'Data imported successfully! Total records: ' . $totalRecords);

        } catch (ValidationException $e) {
            // Handle validation errors from Excel import
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

            // If some records were imported successfully, update the count
            if (isset($fileUpload)) {
                $totalRecords = NadraRecord::where('file_upload_id', $fileUpload->id)->count();
                $fileUpload->update(['total_records' => $totalRecords]);
            }

            $errorMessage = "Import completed with errors:\n" . implode("\n", array_slice($errorMessages, 0, 10));
            if (count($errorMessages) > 10) {
                $errorMessage .= "\n... and " . (count($errorMessages) - 10) . " more errors.";
            }

            return redirect()->back()->with('warning', $errorMessage);

        } catch (\Exception $e) {
            // Clean up file upload record if it was created but import failed
            if (isset($fileUpload)) {
                $fileUpload->delete();
            }

            return redirect()->back()->with('error', 'Error importing data: ' . $e->getMessage());
        }
    }

    public function getUploadedFiles()
    {
        $files = FileUpload::with('nadraRecords')
            ->orderBy('uploaded_at', 'desc')
            ->get();

        return response()->json($files);
    }

    public function getFileData($fileId)
    {
        try {
            $file = FileUpload::findOrFail($fileId);
            $records = NadraRecord::where('file_upload_id', $fileId)->get();

            return response()->json([
                'file' => $file,
                'records' => $records
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'File not found'
            ], 404);
        }
    }

    public function edit($id)
    {
        try {
            $record = NadraRecord::findOrFail($id);
            return response()->json($record);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Record not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $record = NadraRecord::findOrFail($id);
        $category = $record->fileUpload->category;

        $request->validate([
            'full_name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'gender' => 'required|in:Male,Female,Other',
            'date_of_birth' => 'required|date',
            'cnic_number' => [
                'required',
                'regex:/^[0-9]{5}-[0-9]{7}-[0-9]$/',
                Rule::unique('nadra_records', 'cnic_number')->where(function ($query) use ($id) {
                    $record = NadraRecord::findOrFail($id);
                    return $query->where('file_upload_id', $record->file_upload_id);
                })->ignore($id),
            ],
            'family_id' => 'nullable|string|max:255',
            'addresses' => 'nullable|string',
            'province' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
        ]);

        try {
            $record = NadraRecord::findOrFail($id);
            $record->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Record updated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error updating record: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $record = NadraRecord::findOrFail($id);
            $record->delete();

            return response()->json([
                'success' => true,
                'message' => 'Record deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => 'Error deleting record: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkDuplicateCnic(Request $request)
    {
        $cnic = $request->input('cnic');
        $excludeId = $request->input('exclude_id');

        $query = NadraRecord::where('cnic_number', $cnic);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'CNIC number already exists in the database.' : 'CNIC number is available.'
        ]);
    }
}
