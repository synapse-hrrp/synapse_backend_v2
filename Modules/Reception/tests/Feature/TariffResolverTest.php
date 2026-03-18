<?php

namespace Modules\Reception\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Reception\App\Services\TariffResolverService;
use Modules\Reception\App\Models\TariffPlan;
use Modules\Reception\App\Models\BillableService;
use Modules\Reception\App\Models\TariffItem;
use PHPUnit\Framework\Attributes\Test;

class TariffResolverTest extends TestCase
{
    use RefreshDatabase;

    private TariffResolverService $resolver;
    private TariffPlan $plan;
    private BillableService $service;
    private TariffItem $tariffItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(TariffResolverService::class);

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
    }

    #[Test]
    public function resoudre_retourne_tariff_item_correct(): void
    {
        $result = $this->resolver->resoudre(
            categorie: 'laboratory',
            planId:    $this->plan->id,
            serviceId: $this->service->id,
        );

        $this->assertNotNull($result);
        $this->assertEquals(5000, $result->prix_unitaire);
        $this->assertEquals($this->tariffItem->id, $result->id);
    }

    #[Test]
    public function resoudre_retourne_null_si_tarif_inactif(): void
    {
        $this->tariffItem->update(['active' => false]);

        $result = $this->resolver->resoudre(
            categorie: 'laboratory',
            planId:    $this->plan->id,
            serviceId: $this->service->id,
        );

        $this->assertNull($result);
    }

    #[Test]
    public function resoudre_retourne_null_si_mauvaise_categorie(): void
    {
        $result = $this->resolver->resoudre(
            categorie: 'imagerie',
            planId:    $this->plan->id,
            serviceId: $this->service->id,
        );

        $this->assertNull($result);
    }

    #[Test]
    public function lister_plans_retourne_plans_actifs(): void
    {
        TariffPlan::create([
            'nom'    => 'Inactif',
            'type'   => 'standard',
            'active' => false,
        ]);

        $plans = $this->resolver->listerPlans();

        $this->assertCount(1, $plans);
    }

    #[Test]
    public function lister_par_categorie_retourne_services_corrects(): void
    {
        $services = $this->resolver->listerParCategorie(
            categorie: 'laboratory',
            planId:    $this->plan->id,
        );

        $services = collect($services);
        $this->assertCount(1, $services);

        $first = $services->first();

        $code = is_array($first)
            ? ($first['code'] ?? null)
            : ($first->code ?? null);

        $this->assertEquals('LAB001', $code);
    }
}