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
        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // class_teacher, subject_teacher, homeroom
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_assignments');
    }
};
