<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'type' => ['required', 'in:check_in,check_out'],
            'attendance_date' => ['required', 'date'],
            'attendance_time' => ['required', 'date_format:H:i:s'],
            'photo' => ['required', 'file', 'image', 'max:5120'],
        ]);
        $verification = $this->verifyWithFaceService(
            $request->file('photo'),
            (string) $user->id,
        );

        if ($verification['status'] === 'error') {
            return response()->json([
                'status' => 'error',
                'message' => $verification['message'],
                'details' => $verification['details'],
            ], $verification['http_status']);
        }

        $isMatch = (bool) data_get($verification['data'], 'result.is_match', false);

        if (! $isMatch) {
            return response()->json([
                'status' => 'error',
                'message' => 'Face not matched with user.',
                'verification' => $verification['data'],
            ], 422);
        }

        $photoPath = $request->file('photo')->store("attendance/{$user->id}", 'public');

        $log = AttendanceLog::create([
            'user_id' => $user->id,
            'status' => 'matched',
            'type' => $data['type'],
            'attendance_date' => $data['attendance_date'],
            'attendance_time' => $data['attendance_time'],
            'photo_path' => $photoPath,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance stored (face verified).',
            'data' => $log,
            'photo_url' => $photoPath ? Storage::disk('public')->url($photoPath) : null,
            'verification' => $verification['data'],
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

    protected function verifyWithFaceService($file, string $personId): array
    {
        $baseUrl = rtrim(config('services.face_service.base_url'), '/');

        $response = Http::timeout(60)
            ->asMultipart()
            ->attach(
                'file',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName(),
            )
            ->post("{$baseUrl}/verify", [
                'person_name' => $personId,
            ]);

        if ($response->failed()) {
            return [
                'status' => 'error',
                'message' => 'Face service error',
                'details' => $response->json() ?? $response->body(),
                'data' => null,
                'http_status' => $response->status() ?: 502,
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Verified via face service',
            'details' => null,
            'data' => $response->json(),
            'http_status' => $response->status(),
        ];
    }
}
