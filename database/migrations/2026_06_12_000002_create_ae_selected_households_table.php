<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ae_selected_households', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('run_id');
            $table->foreign('run_id')->references('id')->on('ae_selection_runs')->cascadeOnDelete();

            // ── Identifiants REDCap ────────────────────────────────────────────
            $table->string('household_id', 50)->comment('ID REDCap du ménage, ex: DJ-01-PD-01-001');
            $table->string('arm', 10)->comment('PD = PERMANET DUAL, G2 = INTERCEPTOR G2');

            // ── Localisation ──────────────────────────────────────────────────
            $table->string('village', 100)->nullable();
            $table->string('cluster_id', 50)->nullable()->comment('Grappe / cluster');
            $table->string('dag', 100)->nullable()->comment('Data Access Group REDCap');
            $table->string('study_cohort', 10)->nullable()->comment('Cohorte A ou B');
            $table->string('numero_menage', 50)->nullable()->comment('Numéro du ménage dans la grappe');

            // ── Chef de ménage ────────────────────────────────────────────────
            $table->string('head_name', 200)->nullable()->comment('Nom du chef de ménage');
            $table->string('head_phone', 30)->nullable()->comment('Téléphone du chef de ménage');

            // ── Point de contact ──────────────────────────────────────────────
            $table->string('contact_name', 200)->nullable()->comment('Nom du point de contact');
            $table->string('contact_phone', 30)->nullable()->comment('Téléphone du point de contact');

            // ── Moustiquaires ─────────────────────────────────────────────────
            // Nombre de moustiquaires (Form 4b : nb_moustiquaires_etude)
            $table->unsignedSmallInteger('nb_moustiquaires_etude')->nullable();

            // Codes des moustiquaires (Form 4a répétitif : net_identifier_man)
            // Stocké en JSON : ["NET-001", "NET-002", ...]
            $table->json('net_codes')->nullable()->comment('Codes net_identifier_man depuis Form 4a (répétitif)');

            // ── Suivi de complétion du formulaire AE ──────────────────────────
            $table->boolean('form_completed')->default(false);
            $table->timestamp('form_completed_at')->nullable();
            $table->unsignedBigInteger('form_completed_by')->nullable();
            $table->foreign('form_completed_by')->references('id')->on('users')->nullOnDelete();

            $table->timestamps();

            // ── Index pour les filtres fréquents ──────────────────────────────
            $table->index('arm');
            $table->index('village');
            $table->index('cluster_id');
            $table->index('form_completed');
            $table->index(['run_id', 'arm']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ae_selected_households');
    }
};
