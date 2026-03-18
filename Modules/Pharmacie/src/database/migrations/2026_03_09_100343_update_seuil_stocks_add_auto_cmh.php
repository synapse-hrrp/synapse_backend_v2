<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seuil_stocks', function (Blueprint $table) {
            // Renommer colonnes actuelles en "manuel"
            $table->renameColumn('seuil_min', 'seuil_min_manuel');
            $table->renameColumn('seuil_max', 'seuil_max_manuel');
        });

        Schema::table('seuil_stocks', function (Blueprint $table) {
            // Seuils calculés automatiquement
            $table->integer('seuil_min_auto')->nullable()->after('seuil_max_manuel')
                ->comment('Seuil min calculé auto = CMH × 1');
            $table->integer('seuil_max_auto')->nullable()->after('seuil_min_auto')
                ->comment('Seuil max calculé auto = CMH × nb_semaines_couverture');
            
            // Mode de calcul
            $table->enum('mode', ['MANUEL', 'AUTO_CMH'])->default('MANUEL')->after('seuil_max_auto')
                ->comment('MANUEL = utilise seuils manuels, AUTO_CMH = utilise seuils auto basés sur consommation');
            
            // Paramètres calcul auto
            $table->unsignedTinyInteger('nb_semaines_couverture')->default(8)->after('mode')
                ->comment('Nombre de semaines de stock à maintenir (défaut: 8 = 2 mois)');
            
            // Consommations actuelles
            $table->decimal('cmh_actuelle', 10, 2)->nullable()->after('nb_semaines_couverture')
                ->comment('Consommation Moyenne Hebdomadaire actuelle (calculée auto)');
            $table->decimal('cmm_actuelle', 10, 2)->nullable()->after('cmh_actuelle')
                ->comment('Consommation Moyenne Mensuelle actuelle (calculée auto)');
            
            $table->timestamp('derniere_analyse_at')->nullable()->after('cmm_actuelle')
                ->comment('Date dernière analyse consommation');
            
            // Alertes surconsommation
            $table->decimal('seuil_alerte_surconsommation', 5, 2)->default(1.5)->after('derniere_analyse_at')
                ->comment('Seuil alerte surconsommation (1.5 = 150% de la CMH)');
            
            // Index
            $table->index(['produit_id', 'depot_id', 'mode']);
        });

        // Mettre les valeurs actuelles dans les colonnes "manuel"
        DB::statement('UPDATE seuil_stocks SET seuil_min_manuel = seuil_min_manuel WHERE seuil_min_manuel IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('seuil_stocks', function (Blueprint $table) {
            $table->dropColumn([
                'seuil_min_auto',
                'seuil_max_auto',
                'mode',
                'nb_semaines_couverture',
                'cmh_actuelle',
                'cmm_actuelle',
                'derniere_analyse_at',
                'seuil_alerte_surconsommation'
            ]);
        });

        Schema::table('seuil_stocks', function (Blueprint $table) {
            $table->renameColumn('seuil_min_manuel', 'seuil_min');
            $table->renameColumn('seuil_max_manuel', 'seuil_max');
        });
    }
};