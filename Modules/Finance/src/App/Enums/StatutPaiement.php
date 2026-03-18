<?php

namespace Modules\Finance\App\Enums;

enum StatutPaiement: string
{
  case VALIDE = 'valide';
  case ANNULE = 'annule';
}
