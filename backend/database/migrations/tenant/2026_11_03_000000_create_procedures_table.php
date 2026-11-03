<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The procedures that lived in code before, keyed as appointments stored them.
    private const LEGACY = [
        'evaluation' => ['Avaliação', 30],
        'orthodontic_evaluation' => ['Avaliação ortodôntica', 60],
        'cleaning' => ['Limpeza e prevenção', 60],
        'aesthetic_restoration' => ['Restauração estética', 90],
        'follow_up' => ['Consulta de retorno', 30],
    ];

    /** Moves procedures to a table, keeps the price of each appointment and lets rooms be deactivated. */
    public function up(): void
    {
        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedInteger('price_cents')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['active', 'name']);
        });

        $now = now();
        $ids = [];

        foreach (self::LEGACY as $key => [$name, $minutes]) {
            $ids[$key] = DB::table('procedures')->insertGetId([
                'name' => $name,
                'duration_minutes' => $minutes,
                'price_cents' => 0,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('procedure_id')->nullable()->after('room_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('price_cents')->default(0)->after('procedure_id');
        });

        foreach ($ids as $key => $id) {
            DB::table('appointments')->where('procedure', $key)->update(['procedure_id' => $id]);
        }

        // Anything unknown becomes an evaluation, so no appointment is left without a procedure.
        DB::table('appointments')->whereNull('procedure_id')->update(['procedure_id' => $ids['evaluation']]);

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn('procedure');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->boolean('active')->default(true);
        });
    }

    /** Puts the procedure key back on appointments and drops the table. */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('procedure')->default('evaluation');
        });

        foreach (DB::table('procedures')->get() as $procedure) {
            $key = array_search($procedure->name, array_map(fn (array $legacy) => $legacy[0], self::LEGACY), true) ?: 'evaluation';
            DB::table('appointments')->where('procedure_id', $procedure->id)->update(['procedure' => $key]);
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('procedure_id');
            $table->dropColumn('price_cents');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('active');
        });

        Schema::dropIfExists('procedures');
    }
};
