<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accouchements', function (Blueprint $table) {
            $table->id();

            // ── Lien avec la demande autorisée ────────────────────
            $table->foreignId('accouchement_request_id')
                  ->constrained('accouchement_requests')
                  ->restrictOnDelete();

            // ── Équipe médicale ───────────────────────────────────
            $table->foreignId('agent_id')
                  ->nullable()
                  ->constrained('t_agents')
                  ->nullOnDelete();

            // ── Statut ────────────────────────────────────────────
            $table->enum('status', [
                'en_cours',   // Accouchement en cours
                'termine',    // Accouchement terminé
                'complique',  // Complications survenues
                'annule',     // Annulé
            ])->default('en_cours');

            // ── Informations sur l'accouchement ───────────────────
            $table->enum('type_accouchement', [
                'voie_basse',      // Accouchement naturel
                'cesarienne',      // Césarienne
                'voie_basse_instrumentale', // Forceps, ventouse
            ])->nullable();

            // ── Informations sur le nouveau-né ────────────────────
            $table->unsignedTinyInteger('nombre_nouveau_nes')->default(1);

            // Poids à la naissance en grammes
            $table->unsignedSmallInteger('poids_naissance')->nullable();

            // Score d'Apgar (0-10) à 1min et 5min
            $table->unsignedTinyInteger('apgar_1min')->nullable();
            $table->unsignedTinyInteger('apgar_5min')->nullable();

            // Sexe du nouveau-né
            $table->enum('sexe_nouveau_ne', [
                'masculin',
                'feminin',
                'indetermine',
            ])->nullable();

            // ── Informations sur la mère ──────────────────────────
            // Terme de la grossesse en semaines
            $table->unsignedTinyInteger('terme_semaines')->nullable();

            // ── Complications ─────────────────────────────────────
            $table->boolean('complications')->default(false);
            $table->text('details_complications')->nullable();

            // ── Dates clés ────────────────────────────────────────
            $table->timestamp('debut_travail_at')->nullable();
            $table->timestamp('naissance_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            // ── Observations générales ────────────────────────────
            $table->text('observations')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accouchements');
    }
};