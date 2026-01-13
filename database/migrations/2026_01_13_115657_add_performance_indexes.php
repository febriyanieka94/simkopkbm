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
        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->index('payment_date');
            $table->index('student_billing_id');
            // user_id is often queried for filtering transactions by user
            $table->index('user_id'); 
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->index('date');
            $table->index('classroom_id');
            $table->index('teacher_id');
        });

        Schema::table('student_billings', function (Blueprint $table) {
            $table->index('status');
            $table->index('student_id');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['payment_date']);
            $table->dropIndex(['student_billing_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['date']);
            $table->dropIndex(['classroom_id']);
            $table->dropIndex(['teacher_id']);
        });

        Schema::table('student_billings', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['student_id']);
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });
    }
};
