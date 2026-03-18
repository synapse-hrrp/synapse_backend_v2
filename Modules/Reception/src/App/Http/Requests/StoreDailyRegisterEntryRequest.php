<?php

namespace Modules\Reception\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyRegisterEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Ici tu peux mettre une logique permission plus tard (Gate/Policy)
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id'   => ['required', 'integer', 'exists:t_patients,id'],
            'arrival_at'   => ['nullable', 'date'],
            'reason'       => ['nullable', 'string', 'max:2000'],
            'is_emergency' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_id.required' => 'Le patient est obligatoire.',
            'patient_id.exists'   => 'Patient introuvable.',
        ];
    }
}
