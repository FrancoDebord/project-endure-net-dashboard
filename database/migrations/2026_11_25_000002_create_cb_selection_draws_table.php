<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des tirages Cohorte B (initial + compléments) : graine, paramètres, ménages éligibles
 * au moment du tirage et ménages retenus — permet de rejouer et vérifier chaque tirage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cb_selection_draws', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('cb_selection_plans')->cascadeOnDelete();
            $table->string('timepoint', 16);
            $table->string('arm', 4);
            $table->unsignedTinyInteger('batch');
            $table->unsignedBigInteger('seed');
            $table->unsignedSmallInteger('need_nets');
            $table->unsignedTinyInteger('reserve')->default(0);
            $table->boolean('spread_clusters');
            $table->longText('pool');      // JSON [{id, cluster, nets}] — ménages éligibles au moment du tirage
            $table->text('picked');        // JSON [household_id, …] dans l'ordre de visite
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['timepoint', 'arm']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cb_selection_draws');
    }
};
