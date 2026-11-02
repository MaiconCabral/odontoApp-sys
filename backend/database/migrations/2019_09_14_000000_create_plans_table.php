<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Creates the plans table. */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('price_cents')->default(0);
            $table->unsignedInteger('max_professionals')->nullable();
            $table->timestamps();
        });
    }

    /** Drops the plans table. */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
