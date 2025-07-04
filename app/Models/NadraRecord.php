<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\FileUpload;

class NadraRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_upload_id',
        'full_name',
        'father_name',
        'gender',
        'date_of_birth',
        'cnic_number',
        'family_id',
        'addresses',
        'province',
        'district',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function fileUpload()
    {
        return $this->belongsTo(FileUpload::class);
    }
}
