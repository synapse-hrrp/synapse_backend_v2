<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            // Code-barres et identification
            $table->string('code_barre', 50)->unique()->nullable()->after('code');
            $table->string('nom_commercial', 255)->nullable()->after('nom');
            $table->string('molecule', 255)->nullable()->after('nom_commercial');
            
            // Relations
            $table->foreignId('fabricant_id')->nullable()->after('molecule')
                ->constrained('fabricants')->nullOnDelete();
            $table->foreignId('categorie_id')->nullable()->after('fabricant_id')
                ->constrained('categories')->nullOnDelete();
            
            // Prix conseillés
            $table->decimal('prix_vente_conseille', 10, 2)->nullable()->after('prix_achat');
            $table->decimal('coefficient_marge_defaut', 5, 2)->default(1.40)->after('prix_vente_conseille');
            
            // Commande auto
            $table->boolean('commande_automatique')->default(false)->after('coefficient_marge_defaut');
            $table->integer('delai_livraison_jours')->default(7)->after('commande_automatique');
            $table->timestamp('derniere_commande_auto_at')->nullable()->after('delai_livraison_jours');
            
            // Conditionnement
            $table->enum('unite_vente', ['UNITE', 'BOITE', 'STRIP', 'FLACON'])->default('UNITE')->after('derniere_commande_auto_at');
            $table->integer('unites_par_boite')->nullable()->after('unite_vente');
            
            // Index
            $table->index('code_barre');
            $table->index('fabricant_id');
            $table->index('categorie_id');
        });
    }

    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropForeign(['fabricant_id']);
            $table->dropForeign(['categorie_id']);
            $table->dropColumn([
                'code_barre',
                'nom_commercial',
                'molecule',
                'fabricant_id',
                'categorie_id',
                'prix_vente_conseille',
                'coefficient_marge_defaut',
                'commande_automatique',
                'delai_livraison_jours',
                'derniere_commande_auto_at',
                'unite_vente',
                'unites_par_boite'
            ]);
        });
    }
};