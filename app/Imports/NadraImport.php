<?php

namespace App\Imports;

use App\Models\NadraRecord;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;

class NadraImport implements ToModel, WithHeadingRow, WithValidation
{
    use Importable;

    protected $fileUploadId;

    public function __construct($fileUploadId)
    {
        $this->fileUploadId = $fileUploadId;
    }

    public function model(array $row)
    {
        return new NadraRecord([
            'file_upload_id' => $this->fileUploadId,
            'full_name'     => $row['full_name'],
            'father_name'   => $row['father_name'],
            'gender'        => $row['gender'],
            'date_of_birth' => $row['date_of_birth'],
            'cnic_number'   => $row['cnic_number'],
            'family_id'     => $row['family_id'],
            'addresses'     => $row['addresses'],
            'province'      => $row['province'],
            'district'      => $row['district'],
        ]);
    }

    public function rules(): array
    {
        $category = FileUpload::where('id', $this->fileUploadId)->value('category');

        return [
            'cnic_number' => [
                'required',
                'regex:/^\d{5}-\d{7}-\d{1}$/',
                Rule::unique('nadra_records', 'cnic_number')->where(function ($query) {
                    return $query->where('file_upload_id', $this->fileUploadId);
                }),
            ],
            'full_name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'gender' => 'required|in:Male,Female,Other',
            'date_of_birth' => 'required|date',
            'family_id' => 'nullable|string|max:255',
            'addresses' => 'nullable|string',
            'province' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'cnic_number.required' => 'The CNIC number is required.',
            'cnic_number.regex' => 'The CNIC number must be in the format 12345-1234567-1.',
            'cnic_number.unique' => 'The CNIC number has already been taken.',
            'full_name.required' => 'The full name is required.',
            'father_name.required' => 'The father name is required.',
            'gender.required' => 'Gender is required.',
            'gender.in' => 'Gender must be Male, Female, or Other.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.date' => 'Date of birth must be a valid date.',
        ];
    }
}
