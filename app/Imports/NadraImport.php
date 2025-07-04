<?php

namespace App\Imports;

use App\Models\NadraRecord;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\Importable;

class NadraImport implements ToModel, WithHeadingRow
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

    public function customValidationMessages()
    {
        return [
            'cnic_number.regex' => 'The CNIC number must be in the format 12345-1234567-1.',
            'gender.in' => 'Gender must be Male, Female, or Other.',
        ];
    }
}