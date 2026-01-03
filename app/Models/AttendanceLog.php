<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'photo_path',
        'attendance_date',
        'attendance_time',
        'type',
        'attendance_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

        public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

}
