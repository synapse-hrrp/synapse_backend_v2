<?php

namespace Modules\Pharmacie\App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Modules\Pharmacie\App\Interfaces\StockInterface;

class AlertesExport implements WithMultipleSheets
{
    protected $stockService;

    public function __construct(StockInterface $stockService)
    {
        $this->stockService = $stockService;
    }

    public function sheets(): array
    {
        return [
            new StocksExport(
                $this->stockService->getStocksPerimes(),
                'Stocks Périmés'
            ),
            new StocksExport(
                $this->stockService->getStocksProches(30),
                'Proches Péremption (30j)'
            ),
            new StocksExport(
                $this->stockService->getStocksBon(30),
                'Stocks en Bon État'
            ),
        ];
    }
}