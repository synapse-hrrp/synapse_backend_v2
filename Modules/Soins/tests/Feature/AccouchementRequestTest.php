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
use Modules\Soins\App\Models\AccouchementRequest;
use Modules\Soins\App\Models\Accouchement;

class AccouchementRequestTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    private User $userReception;
    private User $userMedecin;
    private User $userInfirmier;
    private TariffPlan $plan;
    private BillableService $service;
    private TariffItem $tariffItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creerPatientEtRegistre();

        $roleReception = Role::create(['label' => 'reception',  'description' => 'Reception']);
        $roleMedecin   = Role::create(['label' => 'medecin',    'description' => 'Medecin']);
        $roleInfirmier = Role::create(['label' => 'infirmier',  'description' => 'Infirmier']);

        $this->userReception = User::factory()->create();
        $this->userReception->roles()->attach($roleReception);

        $this->userMedecin = User::factory()->create();
        $this->userMedecin->roles()->attach($roleMedecin);

        $this->userInfirmier = User::factory()->create();
        $this->userInfirmier->roles()->attach($roleInfirmier);

        $this->plan       = TariffPlan::create(['nom' => 'Standard', 'type' => 'standard', 'active' => true]);
        $this->service    = BillableService::create(['code' => 'ACCOU001', 'libelle' => 'Accouchement voie basse', 'categorie' => 'accouchement', 'active' => true]);
        $this->tariffItem = TariffItem::create(['tariff_plan_id' => $this->plan->id, 'billable_service_id' => $this->service->id, 'prix_unitaire' => 50000, 'active' => true]);
    }

    #[Test]
    public function reception_peut_creer_demande_accouchement(): void
    {
        $response = $this->actingAs($this->userReception)
            ->postJson('/api/v1/soins/accouchement-requests', [
                'patient_id'          => $this->patientId,
                'registre_id'         => $this->registreId,
                'billable_service_id' => $this->service->id,
                'plan_id'             => $this->plan->id,
                'is_urgent'           => false,
            ]);

        $response->assertStatus(201)->assertJson(['success' => true]);

        $this->assertDatabaseHas('accouchement_requests', [
            'unit_price_applied' => 50000,
            'status'             => 'pending_payment',
        ]);
    }

    #[Test]
    public function medecin_ne_peut_pas_creer_demande_accouchement(): void
    {
        $response = $this->actingAs($this->userMedecin)
            ->postJson('/api/v1/soins/accouchement-requests', [
                'patient_id'          => $this->patientId,
                'registre_id'         => $this->registreId,
                'billable_service_id' => $this->service->id,
                'plan_id'             => $this->plan->id,
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function medecin_peut_voir_worklist_accouchements(): void
    {
        AccouchementRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'tariff_item_id'     => $this->tariffItem->id,
            'unit_price_applied' => 50000,
            'status'             => 'authorized',
            'authorized_at'      => now(),
        ]);

        $response = $this->actingAs($this->userMedecin)
            ->getJson('/api/v1/soins/accouchement-requests/worklist');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    #[Test]
    public function infirmier_peut_voir_worklist_accouchements(): void
    {
        AccouchementRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'tariff_item_id'     => $this->tariffItem->id,
            'unit_price_applied' => 50000,
            'status'             => 'authorized',
            'authorized_at'      => now(),
        ]);

        $response = $this->actingAs($this->userInfirmier)
            ->getJson('/api/v1/soins/accouchement-requests/worklist');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }

    #[Test]
    public function reception_ne_peut_pas_voir_worklist_accouchements(): void
    {
        $response = $this->actingAs($this->userReception)
            ->getJson('/api/v1/soins/accouchement-requests/worklist');

        $response->assertStatus(403);
    }

    #[Test]
    public function medecin_peut_demarrer_accouchement_autorise(): void
    {
        $accRequest = AccouchementRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'tariff_item_id'     => $this->tariffItem->id,
            'unit_price_applied' => 50000,
            'status'             => 'authorized',
            'authorized_at'      => now(),
        ]);

        $response = $this->actingAs($this->userMedecin)
            ->postJson('/api/v1/soins/accouchements', [
                'accouchement_request_id' => $accRequest->id,
                'agent_id'                => null,
            ]);

        $response->assertStatus(201)->assertJson(['success' => true]);

        $this->assertDatabaseHas('accouchements', [
            'accouchement_request_id' => $accRequest->id,
            'status'                  => 'en_cours',
        ]);

        $this->assertDatabaseHas('accouchement_requests', [
            'id'     => $accRequest->id,
            'status' => 'in_progress',
        ]);
    }

    #[Test]
    public function demarrer_accouchement_echoue_si_non_autorise(): void
    {
        $accRequest = AccouchementRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'tariff_item_id'     => $this->tariffItem->id,
            'unit_price_applied' => 50000,
            'status'             => 'pending_payment',
        ]);

        $response = $this->actingAs($this->userMedecin)
            ->postJson('/api/v1/soins/accouchements', [
                'accouchement_request_id' => $accRequest->id,
            ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    #[Test]
    public function medecin_peut_terminer_accouchement(): void
    {
        $accRequest = AccouchementRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'tariff_item_id'     => $this->tariffItem->id,
            'unit_price_applied' => 50000,
            'status'             => 'in_progress',
        ]);

        $accouchement = Accouchement::create([
            'accouchement_request_id' => $accRequest->id,
            'status'                  => 'en_cours',
            'debut_travail_at'        => now(),
        ]);

        $response = $this->actingAs($this->userMedecin)
            ->putJson("/api/v1/soins/accouchements/{$accouchement->id}/terminer", [
                'type_accouchement'  => 'voie_basse',
                'nombre_nouveau_nes' => 1,
                'complications'      => false,
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('accouchements', ['id' => $accouchement->id, 'status' => 'termine']);
        $this->assertDatabaseHas('accouchement_requests', ['id' => $accRequest->id, 'status' => 'completed']);
    }
}