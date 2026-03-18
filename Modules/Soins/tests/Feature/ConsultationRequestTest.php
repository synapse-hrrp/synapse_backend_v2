<?php

namespace Modules\Soins\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Users\App\Models\User;
use Modules\Users\App\Models\Role;
use Modules\Reception\App\Models\TariffPlan;
use Modules\Reception\App\Models\BillableService;
use Modules\Reception\App\Models\TariffItem;
use Modules\Soins\App\Models\ConsultationRequest;
use App\Models\Patient;
use App\Models\Personne;
use PHPUnit\Framework\Attributes\Test;

class ConsultationRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $userReception;
    private User $userMedecin;
    private TariffPlan $plan;
    private BillableService $service;
    private TariffItem $tariffItem;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $roleReception = Role::create(['label' => 'reception', 'description' => 'Reception']);
        $roleMedecin   = Role::create(['label' => 'medecin',   'description' => 'Medecin']);

        $this->userReception = User::factory()->create();
        $this->userReception->roles()->attach($roleReception);

        $this->userMedecin = User::factory()->create();
        $this->userMedecin->roles()->attach($roleMedecin);

        $this->plan    = TariffPlan::create(['nom' => 'Standard', 'type' => 'standard', 'active' => true]);
        $this->service = BillableService::create([
            'code'      => 'CONS001',
            'libelle'   => 'Consultation générale',
            'categorie' => 'consultation',
            'active'    => true,
        ]);
        $this->tariffItem = TariffItem::create([
            'tariff_plan_id'      => $this->plan->id,
            'billable_service_id' => $this->service->id,
            'prix_unitaire'       => 5000,
            'active'              => true,
        ]);

        $personne = Personne::factory()->create();

        $this->patient = Patient::create([
            'personne_id' => $personne->id,
            'nip'         => 'NIP-2026-00001',
        ]);
    }

    #[Test]
    public function reception_peut_creer_demande_consultation(): void
    {
        $response = $this->actingAs($this->userReception)
            ->postJson('/api/v1/soins/consultation-requests', [
                'patient_id'          => $this->patient->id,
                'registre_id'         => 1,
                'billable_service_id' => $this->service->id,
                'plan_id'             => $this->plan->id,
                'type_acte'           => 'consultation',
                'is_urgent'           => false,
            ]);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('consultation_requests', [
            'unit_price_applied' => 5000,
            'status'             => 'pending_payment',
        ]);
    }

    #[Test]
    public function medecin_ne_peut_pas_creer_demande_consultation(): void
    {
        $response = $this->actingAs($this->userMedecin)
            ->postJson('/api/v1/soins/consultation-requests', [
                'patient_id'          => $this->patient->id,
                'registre_id'         => 1,
                'billable_service_id' => $this->service->id,
                'plan_id'             => $this->plan->id,
                'type_acte'           => 'consultation',
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function medecin_peut_voir_worklist_consultations(): void
    {
        ConsultationRequest::create([
            'patient_id'         => $this->patient->id,
            'registre_id'        => 1,
            'tariff_item_id'     => $this->tariffItem->id,
            'unit_price_applied' => 5000,
            'type_acte'          => 'consultation',
            'status'             => 'authorized',
            'authorized_at'      => now(),
        ]);

        $response = $this->actingAs($this->userMedecin)
            ->getJson('/api/v1/soins/consultation-requests/worklist');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function reception_ne_peut_pas_voir_worklist_consultations(): void
    {
        $response = $this->actingAs($this->userReception)
            ->getJson('/api/v1/soins/consultation-requests/worklist');

        $response->assertStatus(403);
    }
}