<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Payments received for appointments. A payment is voided, never deleted. */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->string('method');
            $table->date('paid_at');
            $table->string('notes', 500)->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('void_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['appointment_id', 'voided_at']);
            $table->index(['paid_at', 'voided_at']);
        });
    }

    /** Removes the payments. */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
