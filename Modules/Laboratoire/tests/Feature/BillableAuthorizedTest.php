<?php

namespace Modules\Laboratoire\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Modules\Tests\CreatesTestData;
use Modules\Finance\App\Events\BillableAuthorized;
use Modules\Laboratoire\App\Models\ExamenRequest;
use Modules\Laboratoire\App\Models\ExamenType;
use Modules\Reception\App\Models\TariffItem;
use Modules\Reception\App\Models\TariffPlan;
use Modules\Reception\App\Models\BillableService;

class BillableAuthorizedTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->creerPatientEtRegistre();
    }

    #[Test]
    public function event_billable_authorized_autorise_examen_request(): void
    {
        $plan       = TariffPlan::create(['nom' => 'Standard', 'type' => 'standard', 'active' => true]);
        $service    = BillableService::create(['code' => 'LAB001', 'libelle' => 'NFS', 'categorie' => 'laboratory', 'active' => true]);
        $tariffItem = TariffItem::create(['tariff_plan_id' => $plan->id, 'billable_service_id' => $service->id, 'prix_unitaire' => 5000, 'active' => true]);
        $examenType = ExamenType::create(['nom' => 'NFS', 'code' => 'NFS', 'categorie' => 'hematologie', 'active' => true]);

        $examenRequest = ExamenRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'examen_type_id'     => $examenType->id,
            'tariff_item_id'     => $tariffItem->id,
            'unit_price_applied' => 5000,
            'status'             => 'pending_payment',
        ]);

        event(new BillableAuthorized(
            sourceType: 'examen_request',
            sourceId:   $examenRequest->id,
            patientId:  $this->patientId,
        ));

        $this->assertDatabaseHas('examen_requests', [
            'id'     => $examenRequest->id,
            'status' => 'authorized',
        ]);

        $examenRequest->refresh();
        $this->assertNotNull($examenRequest->authorized_at);
    }

    #[Test]
    public function event_billable_authorized_ignore_mauvais_source_type(): void
    {
        $plan       = TariffPlan::create(['nom' => 'Standard', 'type' => 'standard', 'active' => true]);
        $service    = BillableService::create(['code' => 'LAB001', 'libelle' => 'NFS', 'categorie' => 'laboratory', 'active' => true]);
        $tariffItem = TariffItem::create(['tariff_plan_id' => $plan->id, 'billable_service_id' => $service->id, 'prix_unitaire' => 5000, 'active' => true]);
        $examenType = ExamenType::create(['nom' => 'NFS', 'code' => 'NFS', 'categorie' => 'hematologie', 'active' => true]);

        $examenRequest = ExamenRequest::create([
            'patient_id'         => $this->patientId,
            'registre_id'        => $this->registreId,
            'examen_type_id'     => $examenType->id,
            'tariff_item_id'     => $tariffItem->id,
            'unit_price_applied' => 5000,
            'status'             => 'pending_payment',
        ]);

        event(new BillableAuthorized(
            sourceType: 'consultation_request',
            sourceId:   $examenRequest->id,
            patientId:  $this->patientId,
        ));

        $this->assertDatabaseHas('examen_requests', [
            'id'     => $examenRequest->id,
            'status' => 'pending_payment',
        ]);
    }
}