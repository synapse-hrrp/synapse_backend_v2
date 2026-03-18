<?php

namespace Modules\Finance\App\Repositories;

use Modules\Finance\App\Models\FinancePayment;

class FinancePaymentRepository
{
  public function payerTotal(string $tableSource, int $sourceId): float
  {
    return (float) FinancePayment::query()
      ->where('table_source', $tableSource)
      ->where('source_id', $sourceId)
      ->where('statut', 'valide')
      ->sum('montant');
  }
}
