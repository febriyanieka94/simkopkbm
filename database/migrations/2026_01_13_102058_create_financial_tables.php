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
        Schema::create('fee_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. SPP, Pendaftaran, Seragam, Ujian
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('default_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('student_billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('fee_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('month')->nullable(); // For monthly fees like SPP
            $table->decimal('amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_billing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(); // The admin/staf who processed it
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method'); // cash, transfer, etc.
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('student_billings');
        Schema::dropIfExists('fee_categories');
    }
};
