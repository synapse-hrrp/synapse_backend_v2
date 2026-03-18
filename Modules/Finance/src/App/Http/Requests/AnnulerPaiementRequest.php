<?php

namespace Modules\Finance\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnnulerPaiementRequest extends FormRequest
{
  public function authorize(): bool { return true; }

  protected function prepareForValidation(): void
  {
    $this->merge(['poste' => $this->header('X-Workstation')]);
  }

  public function rules(): array
  {
    return [
      'poste'              => 'required|string|max:50',
      'raison_annulation'  => 'required|string|min:3',
    ];
  }
}
