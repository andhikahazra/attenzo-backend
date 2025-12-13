<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'status' => ['required', 'in:matched,not_matched'],
            'type' => ['required', 'in:check_in,check_out'],
            'attendance_date' => ['required', 'date'],
            'attendance_time' => ['required', 'date_format:H:i:s'],
            'photo' => ['required', 'file', 'image', 'max:5120'],
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store("attendance/{$user->id}", 'public');
        }

        $log = AttendanceLog::create([
            'user_id' => $user->id,
            'status' => $data['status'],
            'type' => $data['type'],
            'attendance_date' => $data['attendance_date'],
            'attendance_time' => $data['attendance_time'],
            'photo_path' => $photoPath,
        ]);

        return response()->json([
            'message' => 'Attendance stored.',
            'data' => $log,
            'photo_url' => $photoPath ? Storage::disk('public')->url($photoPath) : null,
        ], 201);
    }


    public function history(Request $request)
    {
        $user = $request->user();

        $logs = $user->attendanceLogs()
            ->latest('attendance_date')
            ->latest('attendance_time')
            ->get();

        return response()->json([
            'data' => $logs,
        ]);
    }
}
