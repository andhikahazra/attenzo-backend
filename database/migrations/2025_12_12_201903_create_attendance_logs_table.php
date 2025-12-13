<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['matched','not_matched'])->default('not_matched'); // hasil verifikasi
            $table->string('photo_path')->nullable(); // foto absensi
            $table->date('attendance_date'); // tanggal
            $table->time('attendance_time'); // jam:menit:detik
            $table->enum('type', ['check_in', 'check_out'])->default('check_in'); // tipe absen
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_logs');
    }
};

