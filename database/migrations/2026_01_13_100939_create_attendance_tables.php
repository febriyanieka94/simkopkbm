<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['h', 's', 'i', 'a']); // Hadir, Sakit, Izin, Alpha
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_items');
        Schema::dropIfExists('attendances');
    }
};
