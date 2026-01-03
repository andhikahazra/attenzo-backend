<?php

namespace App\Http\Controllers;

use App\Models\FacePhoto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class FacePhotoController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        // Validasi input
        $validated = $request->validate([
            'photos' => ['required', 'array', 'size:5'],
            'photos.*' => ['file', 'image', 'max:5120'],
        ]);

        // Ambil data foto lama jika ada
        $existing = FacePhoto::where('user_id', $user->id)->first();
        $oldPaths = $existing?->photo_path ?? [];

        // Simpan foto baru
        $storedPaths = [];
        foreach ($validated['photos'] as $photo) {
            $storedPaths[] = $photo->store("face-photos/{$user->id}", 'public');
        }

        // Hapus foto lama jika ada
        if (!empty($oldPaths)) {
            Storage::disk('public')->delete($oldPaths);
        }

        // Simpan atau update record
        $record = FacePhoto::updateOrCreate(
            ['user_id' => $user->id],
            ['photo_path' => $storedPaths]
        );

        // Response JSON
        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'photo_path' => $record->photo_path,
                'photo_urls' => array_map(
                    fn (string $path) => Storage::disk('public')->url($path),
                    $record->photo_path
                ),
            ],
        ], $existing ? 200 : 201);
    }
    public function show(Request $request)
    {
        $user = $request->user();

        $record = FacePhoto::where('user_id', $user->id)->first();

        if (! $record) {
            return response()->json([
                'message' => 'Face photos not found for this user.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'user_id' => $user->id,
                'photo_path' => $record->photo_path,
                'photo_urls' => array_map(
                    fn (string $path) => Storage::disk('public')->url($path),
                    $record->photo_path
                ),
            ],
        ]);
    }

    public function updateFace(Request $request)
    {
        $user = $request->user(); // user login

        $data = $request->validate([
            'face_embed' => ['required', 'string'],
        ]);

        $user->update([
            'face_embed' => $data['face_embed'],
        ]);

        return response()->json([
            'message' => 'Face embedding saved.',
            'user' => $user->fresh(),
        ]);
    }

    public function registerFace(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'images' => ['required', 'array', 'size:5'],
            'images.*' => ['file', 'image', 'max:5120'],
        ]);

        // Simpan foto ke storage
        $existing = FacePhoto::where('user_id', $user->id)->first();
        $oldPaths = $existing?->photo_path ?? [];

        $storedPaths = [];
        foreach ($data['images'] as $image) {
            $storedPaths[] = $image->store("face-photos/{$user->id}", 'public');
        }

        // Panggil face service
        $baseUrl = rtrim(config('services.face_service.base_url'), '/');
        $http = Http::timeout(60)->asMultipart();
        foreach ($data['images'] as $image) {
            $http = $http->attach(
                'images',
                file_get_contents($image->getRealPath()),
                $image->getClientOriginalName(),
            );
        }

        $response = $http->post("{$baseUrl}/encode/multiple-image", [
            'person_name' => (string) $user->id,
        ]);

        // Jika face service gagal, rollback foto baru
        if ($response->failed()) {
            Storage::disk('public')->delete($storedPaths);

            return response()->json([
                'status' => 'error',
                'message' => 'Face service error',
                'details' => $response->json() ?? $response->body(),
            ], $response->status() ?: 502);
        }

        // Hapus foto lama setelah sukses
        if (! empty($oldPaths)) {
            Storage::disk('public')->delete($oldPaths);
        }

        $record = FacePhoto::updateOrCreate(
            ['user_id' => $user->id],
            ['photo_path' => $storedPaths]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Encoded via face service & photos stored.',
            'data' => $response->json(),
            'photos' => [
                'user_id' => $user->id,
                'photo_path' => $record->photo_path,
                'photo_urls' => array_map(
                    fn (string $path) => Storage::disk('public')->url($path),
                    $record->photo_path
                ),
            ],
        ], $response->status());
    }

    public function verifyWithFaceService(Request $request)
    {
        $data = $request->validate([
            'person_name' => ['required', 'string'],
            'file' => ['required', 'file', 'image', 'max:5120'],
        ]);

        $baseUrl = rtrim(config('services.face_service.base_url'), '/');

        $response = Http::timeout(60)
            ->asMultipart()
            ->attach(
                'file',
                file_get_contents($data['file']->getRealPath()),
                $data['file']->getClientOriginalName(),
            )
            ->post("{$baseUrl}/verify", [
                'person_name' => $data['person_name'],
            ]);

        if ($response->failed()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Face service error',
                'details' => $response->json() ?? $response->body(),
            ], $response->status() ?: 502);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Verification completed via face service',
            'data' => $response->json(),
        ], $response->status());
    }
}


// FacePhotoController.php