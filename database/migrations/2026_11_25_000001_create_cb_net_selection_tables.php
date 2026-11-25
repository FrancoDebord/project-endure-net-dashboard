<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sélection aléatoire des ménages Cohorte B pour le prélèvement des moustiquaires
 * (cible : N moustiquaires par bras et par suivi).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Un plan par suivi × bras : la cible de moustiquaires à prélever
        Schema::create('cb_selection_plans', function (Blueprint $table) {
            $table->id();
            $table->string('timepoint', 16);          // suivi6, suivi12, …
            $table->string('arm', 4);                 // 1 = PERMANET DUAL, 2 = INTERCEPTOR G2
            $table->unsignedSmallInteger('target_nets')->default(30);
            $table->boolean('spread_clusters')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['timepoint', 'arm']);
        });

        // Ménages tirés (ordre de visite = rank). released_at ≠ null → ménage désélectionné, de nouveau disponible
        Schema::create('cb_selected_households', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('cb_selection_plans')->cascadeOnDelete();
            $table->string('timepoint', 16);
            $table->string('arm', 4);
            $table->string('household_id', 40);
            $table->unsignedSmallInteger('rank');
            $table->unsignedTinyInteger('batch')->default(1);   // 1 = tirage initial, 2+ = compléments
            $table->unsignedTinyInteger('nets_planned');        // moustiquaires disponibles au moment du tirage
            $table->timestamp('released_at')->nullable();
            $table->string('release_reason', 120)->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['timepoint', 'household_id']);
            $table->index(['timepoint', 'arm']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cb_selected_households');
        Schema::dropIfExists('cb_selection_plans');
    }
};
