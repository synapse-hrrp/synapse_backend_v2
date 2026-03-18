<?php

namespace Modules\Soins\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Modules\Tests\CreatesTestData;
use Modules\Users\App\Models\User;
use Modules\Users\App\Models\Role;
use Modules\Reception\App\Models\TariffPlan;
use Modules\Reception\App\Models\BillableService;
use Modules\Reception\App\Models\TariffItem;
use Modules\Soins\App\Models\ActeOperatoireRequest;
use Modules\Soins\App\Models\ActeOperatoire;

class ActeOperatoireRequestTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    private User $userReception;
    private User $userMedecin;
    private TariffPlan $plan;
    private BillableService $service;
    private TariffItem $tariffItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creerPatientEtRegistre();

        $roleReception = Role::create(['label' => 'reception', 'description' => 'Reception']);
        $roleMedecin   = Role::create(['label' => 'medecin',   'description' => 'Medecin']);

        $this->userReception = User::factory()->create();
        $this->userReception->roles()->attach($roleReception);

        $this->userMedecin = User::factory()->create();
        $this->userMedecin->roles()->attach($roleMedecin);

        $this->plan       = TariffPlan::create(['nom' => 'Standard', 'type' => 'standard', 'active' => true]);
        $this->service    = BillableService::create(['code' => 'OPER001', 'libelle' => 'Appendicectomie', 'categorie' => 'acte_operatoire', 'active' => true]);
        $this->tariffItem = TariffItem::create(['tariff_plan_id' => $this->plan->id, 'billable_service_id' => $this->service->id, 'prix_unitaire' => 200000, 'active' => true]);
    }

    #[Test]
    public function reception_peut_creer_demande_acte_operatoire(): void
    {
        $response = $this->actingAs($this->userReception)
            ->postJson('/api/v1/soins/acte-operatoire-requests', [
                'patient_id'          => $this->patientId,
                'registre_id'         => $this->registreId,
                'billable_service_id' => $this->service->id,
                'plan_id'             => $this->plan->id,
                'type_operation'      => 'Appendicectomie',
                'is_urgent'           => false,
            ]);

        $response->assertStatus(201)->assertJson(['success' => true]);

        $this->assertDatabaseHas('acte_operatoire_requests', [
            'unit_price_applied' => 200000,
            'status'             => 'pending_payment',
        ]);
    }

    #[Test]
    public function medecin_ne_peut_pas_creer_demande_acte_operatoire(): void
    {
        $response = $this->actingAs($this->userMedecin)
            ->postJson('/api/v1/soins/acte-operatoire-requests', [
                'patient_id'          => $this->patientId,
                'registre_id'         => $this->registreId,
                'billable_service_id' => $this->service->id,
                'plan_id'             => $this->plan->id,
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function medecin_peut_voir_worklist_actes_operatoires(): void
    {
        ActeOperatoireRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'tariff_item_id'     => $this->tariffItem->id,
            'unit_price_applied' => 200000,
            'status'             => 'authorized',
            'authorized_at'      => now(),
        ]);

        $response = $this->actingAs($this->userMedecin)
            ->getJson('/api/v1/soins/acte-operatoire-requests/worklist');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    #[Test]
    public function medecin_peut_demarrer_acte_operatoire_autorise(): void
    {
        $acteRequest = ActeOperatoireRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'tariff_item_id'     => $this->tariffItem->id,
            'unit_price_applied' => 200000,
            'type_operation'     => 'Appendicectomie',
            'status'             => 'authorized',
            'authorized_at'      => now(),
        ]);

        $response = $this->actingAs($this->userMedecin)
            ->postJson('/api/v1/soins/actes-operatoires', [
                'acte_operatoire_request_id' => $acteRequest->id,
                'type_anesthesie'            => 'generale',
                'salle'                      => 'Bloc A',
            ]);

        $response->assertStatus(201)->assertJson(['success' => true]);

        $this->assertDatabaseHas('actes_operatoires', [
            'acte_operatoire_request_id' => $acteRequest->id,
            'status'                     => 'en_cours',
        ]);
    }

    #[Test]
    public function medecin_peut_terminer_acte_operatoire(): void
    {
        $acteRequest = ActeOperatoireRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'tariff_item_id'     => $this->tariffItem->id,
            'unit_price_applied' => 200000,
            'type_operation'     => 'Appendicectomie',
            'status'             => 'in_progress',
        ]);

        $acte = ActeOperatoire::create([
            'acte_operatoire_request_id' => $acteRequest->id,
            'type_operation'             => 'Appendicectomie',
            'status'                     => 'en_cours',
            'debut_at'                   => now(),
        ]);

        $response = $this->actingAs($this->userMedecin)
            ->putJson("/api/v1/soins/actes-operatoires/{$acte->id}/terminer", [
                'compte_rendu'  => 'Intervention réussie sans complications.',
                'complications' => false,
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('actes_operatoires', ['id' => $acte->id, 'status' => 'termine']);
        $this->assertDatabaseHas('acte_operatoire_requests', ['id' => $acteRequest->id, 'status' => 'completed']);
    }
}