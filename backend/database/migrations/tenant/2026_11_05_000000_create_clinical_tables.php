<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Medical record, odontogram, attachments and waiting list of each patient. */
    public function up(): void
    {
        // Append-only: a correction is a new note pointing to the one it amends.
        Schema::create('clinical_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('amends_id')->nullable()->constrained('clinical_notes')->restrictOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
        });

        // History of the teeth; the current state is the latest row of each tooth and surface.
        Schema::create('tooth_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $table->unsignedTinyInteger('tooth');
            $table->string('surface', 1)->nullable();
            $table->string('condition');
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'tooth', 'surface', 'id']);
        });

        Schema::create('patient_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploader_id')->constrained('users')->restrictOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime');
            $table->unsignedInteger('size_bytes');
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
        });

        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('procedure_id')->constrained()->restrictOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('from_date');
            $table->date('to_date');
            $table->string('period')->default('any');
            $table->string('notes', 500)->nullable();
            $table->string('status')->default('waiting');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'from_date', 'to_date']);
        });
    }

    /** Removes the clinical tables. */
    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
        Schema::dropIfExists('patient_attachments');
        Schema::dropIfExists('tooth_records');
        Schema::dropIfExists('clinical_notes');
    }
};
