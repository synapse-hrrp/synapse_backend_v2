<?php

namespace Modules\Finance\App\Repositories;

use Modules\Finance\App\Models\FinanceSession;

class FinanceSessionRepository
{
  public function sessionOuverte(int $userId, string $poste): ?FinanceSession
  {
    return FinanceSession::query()
      ->where('user_id', $userId)
      ->where('poste', $poste)
      ->whereNull('fermee_le')
      ->first();
  }
}
