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
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('pob');
            $table->string('father_name')->nullable()->after('photo');
            $table->string('mother_name')->nullable()->after('father_name');
            $table->string('guardian_name')->nullable()->after('mother_name');
            $table->string('guardian_phone')->nullable()->after('guardian_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn(['photo', 'father_name', 'mother_name', 'guardian_name', 'guardian_phone']);
        });
    }
};
