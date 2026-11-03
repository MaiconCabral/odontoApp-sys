<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Records every message sent to patients and when the reminder went out. */
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('channel');
            $table->string('status');
            $table->string('error')->nullable();
            $table->timestamps();

            $table->index(['appointment_id', 'created_at']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable();
            $table->index(['reminder_sent_at', 'starts_at']);
        });
    }

    /** Removes the message history and the reminder mark. */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['reminder_sent_at', 'starts_at']);
            $table->dropColumn('reminder_sent_at');
        });

        Schema::dropIfExists('notification_logs');
    }
};
