<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** People who run the platform, in the central database. They are not users of any clinic. */
    public function up(): void
    {
        Schema::create('platform_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /** Removes the platform admins. */
    public function down(): void
    {
        Schema::dropIfExists('platform_admins');
    }
};
