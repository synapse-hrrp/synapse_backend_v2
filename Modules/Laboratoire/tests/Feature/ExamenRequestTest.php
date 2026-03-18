<?php

namespace Modules\Laboratoire\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Modules\Tests\CreatesTestData;          // ← AJOUTER
use Modules\Users\App\Models\User;
use Modules\Users\App\Models\Role;
use Modules\Reception\App\Models\TariffPlan;
use Modules\Reception\App\Models\BillableService;
use Modules\Reception\App\Models\TariffItem;
use Modules\Laboratoire\App\Models\ExamenType;
use Modules\Laboratoire\App\Models\ExamenRequest;

class ExamenRequestTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;   // ← AJOUTER CreatesTestData

    private User $userReception;
    private User $userLaborantin;
    private User $userMedecin;
    private TariffPlan $plan;
    private BillableService $service;
    private TariffItem $tariffItem;
    private ExamenType $examenType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->creerPatientEtRegistre(); // ← AJOUTER

        $roleReception  = Role::create(['label' => 'reception',  'description' => 'Reception']);
        $roleLaborantin = Role::create(['label' => 'laborantin', 'description' => 'Laborantin']);
        $roleMedecin    = Role::create(['label' => 'medecin',    'description' => 'Medecin']);

        $this->userReception = User::factory()->create();
        $this->userReception->roles()->attach($roleReception);

        $this->userLaborantin = User::factory()->create();
        $this->userLaborantin->roles()->attach($roleLaborantin);

        $this->userMedecin = User::factory()->create();
        $this->userMedecin->roles()->attach($roleMedecin);

        $this->plan = TariffPlan::create([
            'nom'    => 'Standard',
            'type'   => 'standard',
            'active' => true,
        ]);

        $this->service = BillableService::create([
            'code'      => 'LAB001',
            'libelle'   => 'NFS',
            'categorie' => 'laboratory',
            'active'    => true,
        ]);

        $this->tariffItem = TariffItem::create([
            'tariff_plan_id'      => $this->plan->id,
            'billable_service_id' => $this->service->id,
            'prix_unitaire'       => 5000,
            'active'              => true,
        ]);

        $this->examenType = ExamenType::create([
            'nom'       => 'NFS',
            'code'      => 'NFS',
            'categorie' => 'hematologie',
            'active'    => true,
        ]);
    }

    // ── remplacer patient_id => 1 par $this->patientId ────────

    #[Test]
    public function reception_peut_creer_une_demande_examen(): void
    {
        $response = $this->actingAs($this->userReception)
            ->postJson('/api/v1/laboratoire/examen-requests', [
                'patient_id'          => $this->patientId,      // ← MODIFIER
                'registre_id'         => $this->registreId,     // ← MODIFIER
                'billable_service_id' => $this->service->id,
                'plan_id'             => $this->plan->id,
                'examen_type_id'      => $this->examenType->id,
                'is_urgent'           => false,
            ]);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('examen_requests', [
            'examen_type_id'     => $this->examenType->id,
            'unit_price_applied' => 5000,
            'status'             => 'pending_payment',
        ]);
    }

    #[Test]
    public function laborantin_ne_peut_pas_creer_une_demande_examen(): void
    {
        $response = $this->actingAs($this->userLaborantin)
            ->postJson('/api/v1/laboratoire/examen-requests', [
                'patient_id'          => $this->patientId,
                'registre_id'         => $this->registreId,
                'billable_service_id' => $this->service->id,
                'plan_id'             => $this->plan->id,
                'examen_type_id'      => $this->examenType->id,
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function prix_est_fige_a_la_creation(): void
    {
        $this->actingAs($this->userReception)
            ->postJson('/api/v1/laboratoire/examen-requests', [
                'patient_id'          => $this->patientId,
                'registre_id'         => $this->registreId,
                'billable_service_id' => $this->service->id,
                'plan_id'             => $this->plan->id,
                'examen_type_id'      => $this->examenType->id,
            ]);

        $this->tariffItem->update(['prix_unitaire' => 99999]);

        $this->assertDatabaseHas('examen_requests', [
            'unit_price_applied' => 5000,
        ]);
    }

    #[Test]
    public function creation_echoue_sans_tarif_actif(): void
    {
        $this->tariffItem->update(['active' => false]);

        $response = $this->actingAs($this->userReception)
            ->postJson('/api/v1/laboratoire/examen-requests', [
                'patient_id'          => $this->patientId,
                'registre_id'         => $this->registreId,
                'billable_service_id' => $this->service->id,
                'plan_id'             => $this->plan->id,
                'examen_type_id'      => $this->examenType->id,
            ]);

        $response->assertStatus(422)
                 ->assertJson(['success' => false]);
    }

    #[Test]
    public function laborantin_peut_voir_la_worklist(): void
    {
        ExamenRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'examen_type_id'     => $this->examenType->id,
            'tariff_item_id'     => $this->tariffItem->id,
            'unit_price_applied' => 5000,
            'status'             => 'authorized',
            'authorized_at'      => now(),
        ]);

        $response = $this->actingAs($this->userLaborantin)
            ->getJson('/api/v1/laboratoire/examen-requests/worklist');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function worklist_ne_contient_que_les_demandes_autorisees(): void
    {
        ExamenRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'examen_type_id'     => $this->examenType->id,
            'tariff_item_id'     => $this->tariffItem->id,
            'unit_price_applied' => 5000,
            'status'             => 'authorized',
            'authorized_at'      => now(),
        ]);

        ExamenRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'examen_type_id'     => $this->examenType->id,
            'tariff_item_id'     => $this->tariffItem->id,
            'unit_price_applied' => 5000,
            'status'             => 'pending_payment',
        ]);

        $response = $this->actingAs($this->userLaborantin)
            ->getJson('/api/v1/laboratoire/examen-requests/worklist');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    #[Test]
    public function reception_ne_peut_pas_voir_la_worklist(): void
    {
        $response = $this->actingAs($this->userReception)
            ->getJson('/api/v1/laboratoire/examen-requests/worklist');

        $response->assertStatus(403);
    }

    #[Test]
    public function laborantin_peut_voir_les_demandes_en_attente(): void
    {
        ExamenRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'examen_type_id'     => $this->examenType->id,
            'tariff_item_id'     => $this->tariffItem->id,
            'unit_price_applied' => 5000,
            'status'             => 'pending_payment',
        ]);

        $response = $this->actingAs($this->userLaborantin)
            ->getJson('/api/v1/laboratoire/examen-requests/pending');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }
}