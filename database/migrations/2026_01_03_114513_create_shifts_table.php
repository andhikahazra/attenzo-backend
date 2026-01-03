<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->time('start_time'); // jam masuk
            $table->time('end_time');   // jam pulang

            // toleransi check-in
            $table->integer('early_checkin_tolerance')->default(0); // menit
            $table->integer('late_tolerance')->default(0); // menit

            // toleransi check-out
            $table->integer('early_leave_tolerance')->default(0); // menit

            $table->boolean('is_night_shift')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
