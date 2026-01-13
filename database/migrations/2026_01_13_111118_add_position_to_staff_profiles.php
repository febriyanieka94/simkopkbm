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
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->string('position')->nullable(); // e.g. Kepala Sekolah, Kepala PKBM, Admin
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('level_id')->nullable()->constrained()->nullOnDelete(); // For Kepala Sekolah tiap jenjang
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('level_id');
            $table->dropColumn(['position', 'phone', 'address']);
        });
    }
};
