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
use Modules\Soins\App\Models\HospitalisationRequest;
use Modules\Soins\App\Models\Hospitalisation;

class HospitalisationRequestTest extends TestCase
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
        $this->service    = BillableService::create(['code' => 'HOSP001', 'libelle' => 'Hospitalisation médecine', 'categorie' => 'hospitalisation', 'active' => true]);
        $this->tariffItem = TariffItem::create(['tariff_plan_id' => $this->plan->id, 'billable_service_id' => $this->service->id, 'prix_unitaire' => 15000, 'active' => true]);
    }

    #[Test]
    public function reception_peut_creer_demande_hospitalisation(): void
    {
        $response = $this->actingAs($this->userReception)
            ->postJson('/api/v1/soins/hospitalisation-requests', [
                'patient_id'          => $this->patientId,
                'registre_id'         => $this->registreId,
                'billable_service_id' => $this->service->id,
                'plan_id'             => $this->plan->id,
                'is_urgent'           => false,
            ]);

        $response->assertStatus(201)->assertJson(['success' => true]);

        $this->assertDatabaseHas('hospitalisation_requests', [
            'unit_price_applied' => 15000,
            'status'             => 'pending_payment',
        ]);
    }

    #[Test]
    public function medecin_ne_peut_pas_creer_demande_hospitalisation(): void
    {
        $response = $this->actingAs($this->userMedecin)
            ->postJson('/api/v1/soins/hospitalisation-requests', [
                'patient_id'          => $this->patientId,
                'registre_id'         => $this->registreId,
                'billable_service_id' => $this->service->id,
                'plan_id'             => $this->plan->id,
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function medecin_peut_demarrer_hospitalisation_autorisee(): void
    {
        $hospRequest = HospitalisationRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'tariff_item_id'     => $this->tariffItem->id,
            'unit_price_applied' => 15000,
            'status'             => 'authorized',
            'authorized_at'      => now(),
        ]);

        $response = $this->actingAs($this->userMedecin)
            ->postJson('/api/v1/soins/hospitalisations', [
                'hospitalisation_request_id' => $hospRequest->id,
                'service'                    => 'Médecine interne',
                'chambre'                    => 'A1',
                'lit'                        => '1',
            ]);

        $response->assertStatus(201)->assertJson(['success' => true]);

        $this->assertDatabaseHas('hospitalisations', [
            'hospitalisation_request_id' => $hospRequest->id,
            'status'                     => 'en_cours',
        ]);
    }

    #[Test]
    public function medecin_peut_enregistrer_sortie(): void
    {
        $hospRequest = HospitalisationRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'tariff_item_id'     => $this->tariffItem->id,
            'unit_price_applied' => 15000,
            'status'             => 'in_progress',
        ]);

        $hospitalisation = Hospitalisation::create([
            'hospitalisation_request_id' => $hospRequest->id,
            'status'                     => 'en_cours',
            'admission_at'               => now(),
        ]);

        $response = $this->actingAs($this->userMedecin)
            ->putJson("/api/v1/soins/hospitalisations/{$hospitalisation->id}/sortie", [
                'mode_sortie' => 'guerison',
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('hospitalisations', ['id' => $hospitalisation->id, 'status' => 'termine']);
        $this->assertDatabaseHas('hospitalisation_requests', ['id' => $hospRequest->id, 'status' => 'completed']);
    }
}