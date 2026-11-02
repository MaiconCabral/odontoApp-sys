<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Adds patient records, statuses, opening hours and schedule blocks. */
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->string('email')->nullable()->index();
            $table->string('phone_digits')->nullable()->index();
            $table->string('cpf')->nullable()->unique();
            $table->string('insurance')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('notes')->nullable();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('professional_id')->nullable()->change();
            $table->foreignId('room_id')->nullable()->change();
            $table->string('source')->default('internal');
            $table->text('notes')->nullable();
            $table->string('cancel_reason')->nullable();

            $table->index(['room_id', 'starts_at']);
            $table->index(['status', 'starts_at']);
        });

        Schema::create('appointment_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('reason')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('business_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday')->unique();
            $table->boolean('closed')->default(false);
            $table->time('opens_at')->nullable();
            $table->time('closes_at')->nullable();
            $table->time('break_starts_at')->nullable();
            $table->time('break_ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('schedule_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('reason');
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
        });

        // Every clinic, including the ones registered later, starts with these hours.
        $weekdays = [1, 2, 3, 4, 5];
        $now = now();
        $rows = array_map(fn (int $weekday) => [
            'weekday' => $weekday, 'closed' => false, 'opens_at' => '08:00', 'closes_at' => '18:00',
            'break_starts_at' => '12:00', 'break_ends_at' => '13:00', 'created_at' => $now, 'updated_at' => $now,
        ], $weekdays);
        $rows[] = ['weekday' => 6, 'closed' => false, 'opens_at' => '08:00', 'closes_at' => '12:00', 'break_starts_at' => null, 'break_ends_at' => null, 'created_at' => $now, 'updated_at' => $now];
        $rows[] = ['weekday' => 7, 'closed' => true, 'opens_at' => null, 'closes_at' => null, 'break_starts_at' => null, 'break_ends_at' => null, 'created_at' => $now, 'updated_at' => $now];

        DB::table('business_hours')->insert($rows);
    }

    /** Removes what up() added. */
    public function down(): void
    {
        Schema::dropIfExists('schedule_blocks');
        Schema::dropIfExists('business_hours');
        Schema::dropIfExists('appointment_status_changes');

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['room_id', 'starts_at']);
            $table->dropIndex(['status', 'starts_at']);
            $table->dropColumn(['source', 'notes', 'cancel_reason']);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropUnique(['cpf']);
            $table->dropIndex(['email']);
            $table->dropIndex(['phone_digits']);
            $table->dropColumn(['email', 'phone_digits', 'cpf', 'insurance', 'birth_date', 'notes']);
        });
    }
};
