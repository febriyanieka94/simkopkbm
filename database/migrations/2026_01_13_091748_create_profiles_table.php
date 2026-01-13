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
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('education_level')->nullable();
            $table->timestamps();
        });

        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('nis')->nullable()->unique();
            $table->string('nisn')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->date('dob')->nullable();
            $table->string('pob')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->nullable()->unique();
            $table->string('department')->nullable();
            $table->timestamps();
        });

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('profileable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
