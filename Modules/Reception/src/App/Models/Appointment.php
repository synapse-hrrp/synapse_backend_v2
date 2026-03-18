<?php

namespace Modules\Reception\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Users\App\Models\Agent;

class Appointment extends Model
{
    use SoftDeletes;

    protected $table = 'reception_appointments';

    // DB FR
    protected $fillable = [
        'patient_id',
        'id_medecin_agent',
        'id_agent_createur',
        'id_entree_registre',
        'date_heure',
        'duree_minutes',
        'statut',
        'motif',
        'remarques',
    ];

    protected $casts = [
        'date_heure' => 'datetime',
        'duree_minutes' => 'integer',
    ];

    public const STATUS_BOOKED = 'booked';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_DONE = 'done';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    public static function allowedStatuses(): array
    {
        return [
            self::STATUS_BOOKED,
            self::STATUS_CONFIRMED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_DONE,
            self::STATUS_CANCELLED,
            self::STATUS_NO_SHOW,
        ];
    }

    // Accessors EN (pour que le controller actuel marche sans changer le JSON)
    public function getDoctorAgentIdAttribute() { return $this->id_medecin_agent; }
    public function getCreatedByAgentIdAttribute() { return $this->id_agent_createur; }
    public function getDailyRegisterEntryIdAttribute() { return $this->id_entree_registre; }
    public function getScheduledAtAttribute() { return $this->date_heure; }
    public function getDurationMinutesAttribute() { return $this->duree_minutes; }
    public function getStatusAttribute() { return $this->statut; }
    public function getReasonAttribute() { return $this->motif; }
    public function getNotesAttribute() { return $this->remarques; }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Patient::class, 'patient_id', 'id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'id_medecin_agent', 'id');
    }

    public function createdByAgent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'id_agent_createur', 'id');
    }

    public function registerEntry(): BelongsTo
    {
        return $this->belongsTo(DailyRegisterEntry::class, 'id_entree_registre', 'id');
    }
}
