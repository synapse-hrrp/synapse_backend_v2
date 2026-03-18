<?php

namespace Modules\Pharmacie\App\Models;

use OwenIt\Auditing\Models\Audit as AuditModel;

class PharmacieAudit extends AuditModel
{
    protected $table = 'pharmacie_audits';

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}