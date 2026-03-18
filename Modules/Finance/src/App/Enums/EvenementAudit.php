<?php

namespace Modules\Finance\App\Enums;

enum EvenementAudit: string
{
  case SESSION_OUVERTE = 'SESSION_OUVERTE';
  case SESSION_FERMEE  = 'SESSION_FERMEE';
  case PAIEMENT_CREE   = 'PAIEMENT_CREE';
  case PAIEMENT_ANNULE = 'PAIEMENT_ANNULE';
}
