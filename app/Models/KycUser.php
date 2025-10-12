<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KycUser extends Model
{
    protected $fillable = ['user_id', 'status', 'error_message', 'sex', 'first_name', 'last_name', 'phone', 'date_of_birth', 'country', 'city', 'address', 'zip_code'];

    public const STATUS_OPTIONS = [
        'start' => 'Start',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ];

    public const SEX_OPTIONS = [
        'male' => 'Male',
        'female' => 'Female',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
