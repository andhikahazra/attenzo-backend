<?php

namespace App\Http\Controllers;

use App\Models\FacePhoto;
use App\Models\User;
use Illuminate\Http\Request;
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
}


