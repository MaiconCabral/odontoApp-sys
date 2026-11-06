<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Indexes for the bell, the request queue and the patient list. */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_unread_index');
            $table->index(['notifiable_type', 'notifiable_id', 'created_at'], 'notifications_latest_index');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->index('name');
        });
    }

    /** Drops the performance indexes. */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('notifications_latest_index');
            $table->dropIndex('notifications_unread_index');
        });
    }
};
