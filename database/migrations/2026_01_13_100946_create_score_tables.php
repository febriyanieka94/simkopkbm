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
        Schema::create('score_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Tugas, UTS, UAS, Kuis
            $table->integer('weight')->default(0); // Weighting for final grade calculation
            $table->timestamps();
        });

        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('score_category_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scores');
        Schema::dropIfExists('score_categories');
    }
};
