<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class InstitutionalEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'email',
        'first_name',
        'last_name',
        'department',
        'google_user_id',
        'status',
        'password',
        'google_response',
        'email_created_at',
        'last_synced_at',
        'notes'
    ];

    protected $casts = [
        'google_response' => 'array',
        'email_created_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

 
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }


    public function getPasswordAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

  
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}