<?php

namespace App\Imports;

use App\Models\NadraRecord;
use App\Models\FileUpload;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithUpserts;


class NadraImport implements 
    ToModel, 
    WithHeadingRow, 
    WithValidation, 
    WithBatchInserts, 
    WithChunkReading,
    SkipsEmptyRows
    
{
    use Importable;

    protected $fileUploadId;
    protected $category;

    public function __construct($fileUploadId)
    {
        $this->fileUploadId = $fileUploadId;
        
        // Get category from file upload record
        $fileUpload = FileUpload::find($fileUploadId);
        $this->category = $fileUpload ? $fileUpload->category : null;
    }

    public function model(array $row)
    {
        // Skip if essential fields are missing
        if (empty($row['full_name']) || empty($row['cnic_number'])) {
            return null;
        }

        // Clean and format data
        $cleanRow = $this->cleanRowData($row);

        return new NadraRecord([
            'file_upload_id' => $this->fileUploadId,
            'full_name'      => $cleanRow['full_name'],
            'father_name'    => $cleanRow['father_name'],
            'gender'         => $cleanRow['gender'],
            'date_of_birth'  => $cleanRow['date_of_birth'],
            'cnic_number'    => $cleanRow['cnic_number'],
            'family_id'      => $cleanRow['family_id'],
            'addresses'      => $cleanRow['addresses'],
            'province'       => $cleanRow['province'],
            'district'       => $cleanRow['district'],
        ]);
    }

    private function cleanRowData(array $row)
    {
        return [
            'full_name' => $this->cleanString($row['full_name'] ?? ''),
            'father_name' => $this->cleanString($row['father_name'] ?? ''),
            'gender' => $this->cleanGender($row['gender'] ?? ''),
            'date_of_birth' => $this->cleanDate($row['date_of_birth'] ?? ''),
            'cnic_number' => $this->cleanCnic($row['cnic_number'] ?? ''),
            'family_id' => $this->cleanString($row['family_id'] ?? ''),
            'addresses' => $this->cleanString($row['addresses'] ?? ''),
            'province' => $this->cleanString($row['province'] ?? ''),
            'district' => $this->cleanString($row['district'] ?? ''),
        ];
    }


    private function cleanString($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }
        
        return trim(strip_tags((string)$value));
    }


    private function cleanGender($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        $gender = strtolower(trim((string)$value));
        
        // Map common variations to standard values
        $genderMap = [
            'm' => 'Male',
            'male' => 'Male',
            'man' => 'Male',
            'f' => 'Female',
            'female' => 'Female',
            'woman' => 'Female',
            'o' => 'Other',
            'other' => 'Other',
        ];

        return $genderMap[$gender] ?? null;
    }

    private function cleanDate($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        // If it's already a date object
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d');
        }

        // If it's a numeric Excel date
        if (is_numeric($value)) {
            try {
                // Excel date serial number to date
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                // If conversion fails, treat as string
            }
        }

        // Try to parse as string date
        try {
            $date = new \DateTime($value);
            return $date->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function cleanCnic($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        // Remove all non-numeric characters
        $cnic = preg_replace('/[^0-9]/', '', (string)$value);

        // Check if we have exactly 13 digits
        if (strlen($cnic) !== 13) {
            return (string)$value; // Return original for validation to catch
        }

        // Format as XXXXX-XXXXXXX-X
        return substr($cnic, 0, 5) . '-' . substr($cnic, 5, 7) . '-' . substr($cnic, 12, 1);
    }

     
    public function rules(): array
    {
        return [
            // CNIC validation with uniqueness within same file
            'cnic_number' => [
                'required',
                'string',
                'regex:/^[0-9]{5}-[0-9]{7}-[0-9]$/', // Exact CNIC format
                Rule::unique('nadra_records', 'cnic_number')->where(function ($query) {
                    return $query->where('file_upload_id', $this->fileUploadId);
                }),
            ],
            
            // Personal information validation
            'full_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\.\-\']+$/', // Only letters, spaces, dots, hyphens, apostrophes
            ],
            
            'father_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\.\-\']+$/',
            ],
            
            'gender' => [
                'required',
                'in:Male,Female,Other',
            ],
            
            'date_of_birth' => [
                'required',
                'date',
                'before:today', // Must be in the past
                'after:1900-01-01', // Reasonable birth date range
            ],
            
            // Optional fields with reasonable constraints
            'family_id' => [
                'nullable',
                'string',
                'max:255',
            ],
            
            'addresses' => [
                'nullable',
                'string',
                'max:1000',
            ],
            
            'province' => [
                'nullable',
                'string',
                'max:255',
            ],
            
            'district' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'cnic_number.required' => 'CNIC number is required.',
            'cnic_number.regex' => 'CNIC number must be in format: 12345-1234567-1',
            'cnic_number.unique' => 'CNIC number already exists in this file.',
            
            'full_name.required' => 'Full name is required.',
            'full_name.regex' => 'Full name can only contain letters, spaces, dots, hyphens, and apostrophes.',
            
            'father_name.required' => 'Father name is required.',
            'father_name.regex' => 'Father name can only contain letters, spaces, dots, hyphens, and apostrophes.',
            
            'gender.required' => 'Gender is required.',
            'gender.in' => 'Gender must be Male, Female, or Other.',
            
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.date' => 'Date of birth must be a valid date.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'date_of_birth.after' => 'Date of birth must be after 1900.',
            
            'addresses.max' => 'Address cannot exceed 1000 characters.',
            'province.max' => 'Province name cannot exceed 255 characters.',
            'district.max' => 'District name cannot exceed 255 characters.',
        ];
    }

    public function batchSize(): int
    {
        return 1000;
    }


    public function chunkSize(): int
    {
        return 1000;
    }


    public function isEmptyWhen(array $row): bool
    {
        // Skip if both full_name and cnic_number are empty
        return empty($row['full_name']) && empty($row['cnic_number']);
    }
}