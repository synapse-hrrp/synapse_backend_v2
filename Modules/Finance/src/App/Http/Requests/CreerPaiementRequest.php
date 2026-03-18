<?php

namespace Modules\Finance\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreerPaiementRequest extends FormRequest
{
  public function authorize(): bool { return true; }

  protected function prepareForValidation(): void
  {
    $this->merge(['poste' => $this->header('X-Workstation')]);
  }

  public function rules(): array
  {
    return [
      'poste'         => 'required|string|max:50',
      'module_source' => 'required|string|max:50',
      'table_source'  => 'required|string|max:80',
      'source_id'     => 'required|integer|min:1',
      'montant'       => 'required|numeric|min:0.01',
      'mode'          => 'required|string|max:30',
      'reference'     => 'nullable|string|max:100',
    ];
  }
}
