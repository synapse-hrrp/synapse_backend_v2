<?php

namespace Modules\Finance\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FermerSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // On récupère le header X-Workstation
        $this->merge([
            'poste' => $this->header('X-Workstation')
        ]);
    }

    public function rules(): array
    {
        return [
            'poste' => 'required|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'poste.required' => 'Le header X-Workstation est obligatoire.',
            'poste.string'   => 'Le poste doit être une chaîne de caractères.',
            'poste.max'      => 'Le poste ne doit pas dépasser 50 caractères.',
        ];
    }
}
