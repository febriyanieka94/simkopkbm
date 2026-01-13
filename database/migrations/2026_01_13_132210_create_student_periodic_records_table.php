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
        Schema::create('student_periodic_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->tinyInteger('semester'); // 1 = Ganjil, 2 = Genap
            $table->float('weight')->comment('BB (kg)');
            $table->float('height')->comment('TB (cm)');
            $table->float('head_circumference')->comment('Lingkar Kepala (cm)');
            $table->foreignId('recorded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['student_profile_id', 'academic_year_id', 'semester'], 'student_periodic_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_periodic_records');
    }
};
