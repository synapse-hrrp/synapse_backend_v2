<?php

namespace Modules\Reception\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Reception\App\Models\Appointment;
use Modules\Reception\App\Models\DailyRegisterEntry;

class AppointmentsController extends Controller
{
    private function baseWith(): array
    {
        return [
            'patient:id,personne_id',
            'patient.personne:id,nom,prenom',
            'doctor:id,matricule',
            'createdByAgent:id,matricule',
            'registerEntry:id',
        ];
    }

    private function patientDisplay($patient): string
    {
        $p = $patient?->personne;
        $display = trim(($p?->prenom ?? '') . ' ' . ($p?->nom ?? ''));
        return $display !== '' ? $display : ('Patient #' . ($patient?->id ?? 'N/A'));
    }

    private function format(Appointment $a): array
    {
        $patient = $a->patient;

        return [
            'id' => $a->id,

            'patient_id' => $a->patient_id,
            'patient' => [
                'id' => $patient?->id,
                'personne_id' => $patient?->personne_id,
                'display' => $this->patientDisplay($patient),
            ],

            'doctor_agent_id' => $a->doctor_agent_id,
            'doctor' => $a->doctor ? [
                'id' => $a->doctor->id,
                'matricule' => $a->doctor->matricule,
            ] : null,

            'created_by_agent_id' => $a->created_by_agent_id,
            'created_by_agent' => $a->createdByAgent ? [
                'id' => $a->createdByAgent->id,
                'matricule' => $a->createdByAgent->matricule,
            ] : null,

            'daily_register_entry_id' => $a->daily_register_entry_id,

            'scheduled_at' => optional($a->scheduled_at)->toISOString(),
            'duration_minutes' => (int) $a->duration_minutes,

            'status' => $a->status,
            'reason' => $a->reason,
            'notes' => $a->notes,

            'created_at' => optional($a->created_at)->toISOString(),
            'updated_at' => optional($a->updated_at)->toISOString(),
        ];
    }

    public function index(Request $request)
    {
        $qDate = $request->query('date'); // YYYY-MM-DD
        $doctorId = $request->query('doctor_agent_id');
        $status = $request->query('status');

        $query = Appointment::query()
            ->with($this->baseWith())
            ->orderBy('date_heure'); // ✅ DB FR

        if ($qDate) {
            $query->whereDate('date_heure', $qDate); // ✅ DB FR
        }

        if ($doctorId) {
            $query->where('id_medecin_agent', (int) $doctorId); // ✅ DB FR
        }

        if ($status) {
            $query->where('statut', $status); // ✅ DB FR
        }

        $page = $query->paginate(10);
        $page->getCollection()->transform(fn ($a) => $this->format($a));

        return response()->json([
            'message' => 'Rendez-vous',
            'data' => $page,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:t_patients,id'],
            'doctor_agent_id' => ['nullable', 'integer', 'exists:t_agents,id'],
            'daily_register_entry_id' => ['nullable', 'integer', 'exists:daily_register_entries,id'],

            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],

            'status' => ['nullable', 'string', Rule::in(Appointment::allowedStatuses())],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
        ]);

        $agentId = $request->user()?->agent_id;

        if (!empty($data['doctor_agent_id'])) {
            $start = Carbon::parse($data['scheduled_at']);
            $duration = (int) ($data['duration_minutes'] ?? 30);
            $end = (clone $start)->addMinutes($duration);

            $conflict = Appointment::query()
                ->where('id_medecin_agent', (int) $data['doctor_agent_id']) // ✅ DB FR
                ->whereNull('deleted_at')
                ->whereNotIn('statut', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW]) // ✅ DB FR
                ->whereRaw(
                    'date_heure < ? AND DATE_ADD(date_heure, INTERVAL duree_minutes MINUTE) > ?', // ✅ DB FR
                    [$end, $start]
                )
                ->exists();

            if ($conflict) {
                return response()->json(['message' => 'Le médecin a déjà un rendez-vous sur ce créneau.'], 422);
            }
        }

        $appt = Appointment::query()->create([
            'patient_id' => $data['patient_id'],
            'id_medecin_agent' => $data['doctor_agent_id'] ?? null,
            'id_agent_createur' => $agentId,
            'id_entree_registre' => $data['daily_register_entry_id'] ?? null,

            'date_heure' => $data['scheduled_at'],
            'duree_minutes' => $data['duration_minutes'] ?? 30,

            'statut' => $data['status'] ?? Appointment::STATUS_BOOKED,
            'motif' => $data['reason'] ?? null,
            'remarques' => $data['notes'] ?? null,
        ])->fresh($this->baseWith());

        return response()->json([
            'message' => 'Rendez-vous créé',
            'data' => $this->format($appt),
        ], 201);
    }

    public function show(int $id)
    {
        $appt = Appointment::query()->with($this->baseWith())->findOrFail($id);

        return response()->json([
            'message' => 'Détails rendez-vous',
            'data' => $this->format($appt),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $appt = Appointment::query()->findOrFail($id);

        $data = $request->validate([
            'doctor_agent_id' => ['sometimes', 'nullable', 'integer', 'exists:t_agents,id'],
            'scheduled_at' => ['sometimes', 'date'],
            'duration_minutes' => ['sometimes', 'integer', 'min:5', 'max:480'],

            'status' => ['sometimes', 'string', Rule::in(Appointment::allowedStatuses())],
            'reason' => ['sometimes', 'nullable', 'string', 'max:500'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $doctorId = array_key_exists('doctor_agent_id', $data) ? $data['doctor_agent_id'] : $appt->doctor_agent_id;

        $scheduledAt = array_key_exists('scheduled_at', $data)
            ? Carbon::parse($data['scheduled_at'])
            : Carbon::parse($appt->scheduled_at);

        $duration = array_key_exists('duration_minutes', $data)
            ? (int) $data['duration_minutes']
            : (int) $appt->duration_minutes;

        if (!empty($doctorId)) {
            $start = $scheduledAt;
            $end = (clone $start)->addMinutes($duration);

            $conflict = Appointment::query()
                ->where('id_medecin_agent', (int) $doctorId)
                ->where('id', '!=', $appt->id)
                ->whereNull('deleted_at')
                ->whereNotIn('statut', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
                ->whereRaw(
                    'date_heure < ? AND DATE_ADD(date_heure, INTERVAL duree_minutes MINUTE) > ?',
                    [$end, $start]
                )
                ->exists();

            if ($conflict) {
                return response()->json(['message' => 'Le médecin a déjà un rendez-vous sur ce créneau.'], 422);
            }
        }

        // ✅ convert payload EN -> DB FR
        $update = [];
        if (array_key_exists('doctor_agent_id', $data)) $update['id_medecin_agent'] = $data['doctor_agent_id'];
        if (array_key_exists('scheduled_at', $data)) $update['date_heure'] = $data['scheduled_at'];
        if (array_key_exists('duration_minutes', $data)) $update['duree_minutes'] = $data['duration_minutes'];
        if (array_key_exists('status', $data)) $update['statut'] = $data['status'];
        if (array_key_exists('reason', $data)) $update['motif'] = $data['reason'];
        if (array_key_exists('notes', $data)) $update['remarques'] = $data['notes'];

        $appt->update($update);
        $appt = $appt->fresh($this->baseWith());

        return response()->json([
            'message' => 'Rendez-vous mis à jour',
            'data' => $this->format($appt),
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $appt = Appointment::query()->findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(Appointment::allowedStatuses())],
        ]);

        $appt->statut = $data['status'];
        $appt->save();

        $appt = $appt->fresh($this->baseWith());

        return response()->json([
            'message' => 'Statut mis à jour',
            'data' => $this->format($appt),
        ]);
    }

    public function availability(Request $request)
    {
        $data = $request->validate([
            'doctor_agent_id' => ['required', 'integer', 'exists:t_agents,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
        ]);

        $doctorId = (int) $data['doctor_agent_id'];
        $day = Carbon::createFromFormat('Y-m-d', $data['date']);
        $duration = (int) ($data['duration_minutes'] ?? 30);

        $startDay = $day->copy()->setTime(8, 0, 0);
        $endDay   = $day->copy()->setTime(17, 0, 0);

        $appts = Appointment::query()
            ->where('id_medecin_agent', $doctorId)
            ->whereDate('date_heure', $day->toDateString())
            ->whereNotIn('statut', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
            ->orderBy('date_heure')
            ->get(['date_heure', 'duree_minutes']);

        $busy = $appts->map(function ($a) {
            $s = Carbon::parse($a->date_heure);
            $e = (clone $s)->addMinutes((int) $a->duree_minutes);
            return ['start' => $s, 'end' => $e];
        })->all();

        $slots = [];
        $cursor = $startDay->copy();

        while ($cursor->copy()->addMinutes($duration)->lte($endDay)) {
            $slotStart = $cursor->copy();
            $slotEnd = $cursor->copy()->addMinutes($duration);

            $conflict = false;
            foreach ($busy as $b) {
                if ($slotStart->lt($b['end']) && $slotEnd->gt($b['start'])) {
                    $conflict = true;
                    break;
                }
            }

            if (!$conflict) {
                $slots[] = [
                    'start' => $slotStart->toISOString(),
                    'end' => $slotEnd->toISOString(),
                ];
            }

            $cursor->addMinutes(15);
        }

        return response()->json([
            'message' => 'Disponibilités médecin',
            'data' => [
                'doctor_agent_id' => $doctorId,
                'date' => $day->toDateString(),
                'duration_minutes' => $duration,
                'slots' => $slots,
            ],
        ]);
    }

    public function createFromRegister(Request $request, int $id)
    {
        $entry = DailyRegisterEntry::query()->findOrFail($id);

        $data = $request->validate([
            'doctor_agent_id' => ['nullable', 'integer', 'exists:t_agents,id'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
            'reason' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
        ]);

        $agentId = $request->user()?->agent_id;

        $appt = Appointment::query()->create([
            'patient_id' => $entry->patient_id,
            'id_medecin_agent' => $data['doctor_agent_id'] ?? null,
            'id_agent_createur' => $agentId,
            'id_entree_registre' => $entry->id,

            'date_heure' => $data['scheduled_at'],
            'duree_minutes' => $data['duration_minutes'] ?? 30,
            'statut' => Appointment::STATUS_BOOKED,

            'motif' => $data['reason'] ?? null,
            'remarques' => $data['notes'] ?? null,
        ])->fresh($this->baseWith());

        return response()->json([
            'message' => 'Rendez-vous créé depuis le registre',
            'data' => $this->format($appt),
        ], 201);
    }

    public function reschedule(Request $request, int $id)
    {
        $appt = Appointment::query()->findOrFail($id);

        $data = $request->validate([
            'doctor_agent_id' => ['nullable', 'integer', 'exists:t_agents,id'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
        ]);

        $doctorId = $data['doctor_agent_id'] ?? $appt->doctor_agent_id;
        $start = Carbon::parse($data['scheduled_at']);
        $duration = (int)($data['duration_minutes'] ?? $appt->duration_minutes ?? 30);
        $end = (clone $start)->addMinutes($duration);

        if (!empty($doctorId)) {
            $conflict = Appointment::query()
                ->where('id_medecin_agent', (int) $doctorId)
                ->where('id', '!=', $appt->id)
                ->whereNull('deleted_at')
                ->whereNotIn('statut', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
                ->whereRaw(
                    'date_heure < ? AND DATE_ADD(date_heure, INTERVAL duree_minutes MINUTE) > ?',
                    [$end, $start]
                )
                ->exists();

            if ($conflict) {
                return response()->json(['message' => 'Le médecin a déjà un rendez-vous sur ce créneau.'], 422);
            }
        }

        $appt->update([
            'id_medecin_agent' => $doctorId,
            'date_heure' => $start,
            'duree_minutes' => $duration,
        ]);

        $appt = $appt->fresh($this->baseWith());

        return response()->json([
            'message' => 'Rendez-vous replanifié',
            'data' => $this->format($appt),
        ]);
    }

    public function dayView(Request $request)
    {
        $data = $request->validate([
            'doctor_agent_id' => ['required', 'integer', 'exists:t_agents,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'duration_minutes' => ['nullable', 'integer', 'min:5', 'max:480'],
        ]);

        $doctorId = (int) $data['doctor_agent_id'];
        $day = Carbon::createFromFormat('Y-m-d', $data['date']);
        $duration = (int)($data['duration_minutes'] ?? 30);

        $appointments = Appointment::query()
            ->with($this->baseWith())
            ->where('id_medecin_agent', $doctorId)
            ->whereDate('date_heure', $day->toDateString())
            ->whereNotIn('statut', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
            ->orderBy('date_heure')
            ->get()
            ->map(fn($a) => $this->format($a))
            ->values();

        $startDay = $day->copy()->setTime(8, 0, 0);
        $endDay   = $day->copy()->setTime(17, 0, 0);

        $busy = Appointment::query()
            ->where('id_medecin_agent', $doctorId)
            ->whereDate('date_heure', $day->toDateString())
            ->whereNotIn('statut', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
            ->orderBy('date_heure')
            ->get(['date_heure', 'duree_minutes'])
            ->map(function ($a) {
                $s = Carbon::parse($a->date_heure);
                $e = (clone $s)->addMinutes((int)$a->duree_minutes);
                return ['start' => $s, 'end' => $e];
            })
            ->all();

        $slots = [];
        $cursor = $startDay->copy();

        while ($cursor->copy()->addMinutes($duration)->lte($endDay)) {
            $slotStart = $cursor->copy();
            $slotEnd = $cursor->copy()->addMinutes($duration);

            $conflict = false;
            foreach ($busy as $b) {
                if ($slotStart->lt($b['end']) && $slotEnd->gt($b['start'])) {
                    $conflict = true;
                    break;
                }
            }

            if (!$conflict) {
                $slots[] = [
                    'start' => $slotStart->toISOString(),
                    'end' => $slotEnd->toISOString(),
                ];
            }

            $cursor->addMinutes(15);
        }

        return response()->json([
            'message' => 'Planning du jour',
            'data' => [
                'doctor_agent_id' => $doctorId,
                'date' => $day->toDateString(),
                'duration_minutes' => $duration,
                'appointments' => $appointments,
                'slots' => $slots,
            ],
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $appt = Appointment::query()->findOrFail($id);

        // (optionnel) si tu veux interdire la suppression d'un RDV "done"
        // if ($appt->status === Appointment::STATUS_DONE) {
        //     return response()->json(['message' => 'Impossible de supprimer un rendez-vous terminé.'], 422);
        // }

        // Si ton modèle Appointment utilise SoftDeletes -> soft delete
        // Sinon -> delete normal
        $appt->delete();

        return response()->json([
            'message' => 'Rendez-vous supprimé',
            'data' => [
                'id' => $id,
            ],
        ]);
    }



}
