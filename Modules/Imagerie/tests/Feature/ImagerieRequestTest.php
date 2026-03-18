<?php

namespace Modules\Imagerie\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Modules\Tests\CreatesTestData;
use Modules\Users\App\Models\User;
use Modules\Users\App\Models\Role;
use Modules\Reception\App\Models\TariffPlan;
use Modules\Reception\App\Models\BillableService;
use Modules\Reception\App\Models\TariffItem;
use Modules\Imagerie\App\Models\ImagerieType;
use Modules\Imagerie\App\Models\ImagerieRequest;

class ImagerieRequestTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    private User $userReception;
    private User $userMedecin;
    private TariffPlan $plan;
    private BillableService $service;
    private TariffItem $tariffItem;
    private ImagerieType $imagerieType;

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

        $this->plan         = TariffPlan::create(['nom' => 'Standard', 'type' => 'standard', 'active' => true]);
        $this->service      = BillableService::create(['code' => 'IMG001', 'libelle' => 'Radio thorax', 'categorie' => 'imagerie', 'active' => true]);
        $this->tariffItem   = TariffItem::create(['tariff_plan_id' => $this->plan->id, 'billable_service_id' => $this->service->id, 'prix_unitaire' => 10000, 'active' => true]);
        $this->imagerieType = ImagerieType::create(['nom' => 'Radio thorax', 'code' => 'RX-THORAX', 'categorie' => 'radiographie', 'active' => true]);
    }

    #[Test]
    public function reception_peut_creer_demande_imagerie(): void
    {
        $response = $this->actingAs($this->userReception)
            ->postJson('/api/v1/imagerie/imagerie-requests', [
                'patient_id'          => $this->patientId,
                'registre_id'         => $this->registreId,
                'billable_service_id' => $this->service->id,
                'plan_id'             => $this->plan->id,
                'imagerie_type_id'    => $this->imagerieType->id,
                'is_urgent'           => false,
            ]);

        $response->assertStatus(201)->assertJson(['success' => true]);

        $this->assertDatabaseHas('imagerie_requests', [
            'imagerie_type_id'   => $this->imagerieType->id,
            'unit_price_applied' => 10000,
            'status'             => 'pending_payment',
        ]);
    }

    #[Test]
    public function medecin_ne_peut_pas_creer_demande_imagerie(): void
    {
        $response = $this->actingAs($this->userMedecin)
            ->postJson('/api/v1/imagerie/imagerie-requests', [
                'patient_id'          => $this->patientId,
                'registre_id'         => $this->registreId,
                'billable_service_id' => $this->service->id,
                'plan_id'             => $this->plan->id,
                'imagerie_type_id'    => $this->imagerieType->id,
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function worklist_imagerie_retourne_demandes_autorisees(): void
    {
        ImagerieRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'imagerie_type_id'   => $this->imagerieType->id,
            'tariff_item_id'     => $this->tariffItem->id,
            'unit_price_applied' => 10000,
            'status'             => 'authorized',
            'authorized_at'      => now(),
        ]);

        $response = $this->actingAs($this->userMedecin)
            ->getJson('/api/v1/imagerie/imagerie-requests/worklist');

        $response->assertStatus(200)->assertJsonCount(1, 'data');
    }
}