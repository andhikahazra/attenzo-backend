<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * STORE ATTENDANCE (CHECK IN / CHECK OUT)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'type' => ['required', 'in:check_in,check_out'],
            'attendance_date' => ['required', 'date'],
            'attendance_time' => ['required', 'date_format:H:i:s'],
            'photo' => ['required', 'file', 'image', 'max:5120'],
        ]);

        /**
         * FACE VERIFICATION
         */
        $verification = $this->verifyWithFaceService(
            $request->file('photo'),
            (string) $user->id
        );

        if ($verification['status'] === 'error') {
            return response()->json($verification, $verification['http_status']);
        }

        if (! data_get($verification['data'], 'result.is_match')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Face not matched with user.',
            ], 422);
        }

        /**
         * AMBIL SHIFT USER
         * (asumsi user punya shift_id)
         */
        $shift = Shift::find($user->shift_id);

        if (! $shift) {
            return response()->json([
                'status' => 'error',
                'message' => 'Shift not assigned to user.',
            ], 422);
        }

        /**
         * VALIDASI WAKTU ABSENSI (MAKSIMAL X JAM SETELAH WAKTU SHIFT)
         */
        $attendanceTime = Carbon::createFromFormat('H:i:s', $data['attendance_time']);
        
        if ($data['type'] === 'check_in') {
            $maxCheckInTime = Carbon::createFromFormat('H:i:s', $shift->start_time)
                ->addHours($shift->max_checkin_hours ?? 2);
            
            if ($attendanceTime->gt($maxCheckInTime)) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Check in tidak diperbolehkan lebih dari {$shift->max_checkin_hours} jam setelah jam shift ({$shift->start_time}). Maksimal check in: {$maxCheckInTime->format('H:i:s')}",
                ], 422);
            }
        }
        
        if ($data['type'] === 'check_out') {
            $maxCheckOutTime = Carbon::createFromFormat('H:i:s', $shift->end_time)
                ->addHours($shift->max_checkout_hours ?? 2);
            
            if ($attendanceTime->gt($maxCheckOutTime)) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Check out tidak diperbolehkan lebih dari {$shift->max_checkout_hours} jam setelah jam shift ({$shift->end_time}). Maksimal check out: {$maxCheckOutTime->format('H:i:s')}",
                ], 422);
            }
        }

        /**
         * HITUNG ATTENDANCE STATUS
         */
        $attendanceStatus = null;

        if ($data['type'] === 'check_in') {
            $startTime = Carbon::createFromFormat('H:i:s', $shift->start_time)
                ->addMinutes($shift->late_tolerance);

            $attendanceStatus = $attendanceTime->lte($startTime)
                ? 'on_time'
                : 'late';
        }

        if ($data['type'] === 'check_out') {
            $endTime = Carbon::createFromFormat('H:i:s', $shift->end_time)
                ->subMinutes($shift->early_leave_tolerance);

            $attendanceStatus = $attendanceTime->lt($endTime)
                ? 'early_leave'
                : 'on_time';
        }

        /**
         * SIMPAN FOTO
         */
        $photoPath = $request->file('photo')
            ->store("attendance/{$user->id}", 'public');

        /**
         * SIMPAN LOG
         */
        $log = AttendanceLog::create([
            'user_id' => $user->id,
            'shift_id' => $shift->id,
            'type' => $data['type'],
            'status' => 'matched',
            'attendance_status' => $attendanceStatus,
            'attendance_date' => $data['attendance_date'],
            'attendance_time' => $data['attendance_time'],
            'photo_path' => $photoPath,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance stored successfully.',
            'data' => $log,
            'photo_url' => Storage::disk('public')->url($photoPath),
        ], 201);
    }

    /**
     * STATUS TOMBOL ABSENSI HARI INI
     */
    public function todayStatus(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        $checkIn = AttendanceLog::where('user_id', $user->id)
            ->where('attendance_date', $today)
            ->where('type', 'check_in')
            ->first();

        $checkOut = AttendanceLog::where('user_id', $user->id)
            ->where('attendance_date', $today)
            ->where('type', 'check_out')
            ->first();

        return response()->json([
            'date' => $today,

            'can_check_in' => ! $checkIn,
            'can_check_out' => $checkIn && ! $checkOut,

            'status' => match (true) {
                ! $checkIn => 'not_checked_in',
                $checkIn && ! $checkOut => 'checked_in',
                default => 'completed',
            },

            'check_in' => $checkIn ? [
                'time' => $checkIn->attendance_time,
                'attendance_status' => $checkIn->attendance_status,
            ] : null,

            'check_out' => $checkOut ? [
                'time' => $checkOut->attendance_time,
                'attendance_status' => $checkOut->attendance_status,
            ] : null,
        ]);
    }

    /**
     * RIWAYAT ABSENSI
     */
    public function history(Request $request)
    {
        return response()->json([
            'data' => $request->user()
                ->attendanceLogs()
                ->latest('attendance_date')
                ->latest('attendance_time')
                ->get(),
        ]);
    }

    /**
     * FACE SERVICE
     */
    protected function verifyWithFaceService($file, string $personId): array
    {
        $baseUrl = rtrim(config('services.face_service.base_url'), '/');

        $response = Http::timeout(60)
            ->asMultipart()
            ->attach(
                'file',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )
            ->post("{$baseUrl}/verify", [
                'person_name' => $personId,
            ]);

        if ($response->failed()) {
            return [
                'status' => 'error',
                'message' => 'Face service error',
                'details' => $response->json() ?? $response->body(),
                'http_status' => $response->status() ?: 502,
            ];
        }

        return [
            'status' => 'success',
            'data' => $response->json(),
            'http_status' => $response->status(),
        ];
    }
}
