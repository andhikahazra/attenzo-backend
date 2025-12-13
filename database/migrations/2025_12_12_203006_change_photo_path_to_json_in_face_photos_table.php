<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('face_photos', function (Blueprint $table) {
            $table->json('photo_path')->change(); // ubah tipe column menjadi JSON
        });
    }

    public function down(): void
    {
        Schema::table('face_photos', function (Blueprint $table) {
            $table->string('photo_path')->change(); // kembalikan ke string
        });
    }
};
