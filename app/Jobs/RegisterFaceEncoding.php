<?php

namespace App\Jobs;

use App\Models\FacePhoto;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class RegisterFaceEncoding implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public array $photoPaths
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user) return;

        $baseUrl = rtrim(config('services.face_service.base_url'), '/');

        $http = Http::timeout(60)->asMultipart();

        foreach ($this->photoPaths as $path) {
            $filePath = Storage::disk('public')->path($path);

            if (! file_exists($filePath)) continue;

            $http = $http->attach(
                'images',
                file_get_contents($filePath),
                basename($filePath)
            );
        }

        $response = $http->post("{$baseUrl}/encode/multiple-image", [
            'person_name' => (string) $user->id,
        ]);

        if ($response->failed()) {
            // kalau gagal, jangan hapus foto (biar bisa retry)
            return;
        }

        FacePhoto::updateOrCreate(
            ['user_id' => $user->id],
            ['photo_path' => $this->photoPaths]
        );
    }
}
