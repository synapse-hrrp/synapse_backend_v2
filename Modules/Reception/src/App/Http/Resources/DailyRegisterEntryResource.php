<?php

namespace Modules\Reception\App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DailyRegisterEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,

            'patient_id' => $this->patient_id,
            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'matricule' => $this->patient->matricule ?? null,
                    'personne' => $this->whenLoaded('patient.personne', function () {
                        return $this->patient->personne;
                    }),
                ];
            }),

            'created_by_agent_id' => $this->created_by_agent_id,
            'created_by_agent' => $this->whenLoaded('createdByAgent', function () {
                return [
                    'id' => $this->createdByAgent->id,
                    'matricule' => $this->createdByAgent->matricule ?? null,
                    'personne' => $this->whenLoaded('createdByAgent.personne', function () {
                        return $this->createdByAgent->personne;
                    }),
                ];
            }),

            'arrival_at' => optional($this->arrival_at)->toISOString(),
            'reason' => $this->reason,
            'is_emergency' => (bool) $this->is_emergency,
            'status' => $this->status,
            'billing_request_id' => $this->billing_request_id,

            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
