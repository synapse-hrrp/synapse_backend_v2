<?php

namespace Modules\Finance\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OuvrirSessionRequest extends FormRequest
{
  public function authorize(): bool { return true; }

  protected function prepareForValidation(): void
  {
    $this->merge(['poste' => $this->header('X-Workstation')]);
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
      'poste.required' => 'Header X-Workstation obligatoire',
    ];
  }
}
