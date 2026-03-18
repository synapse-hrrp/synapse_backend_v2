<?php

namespace Modules\Finance\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FiltrerAuditRequest extends FormRequest
{
  public function authorize(): bool { return true; }

  public function rules(): array
  {
    return [
      'session_id' => 'nullable|integer|min:1',
      'evenement'  => 'nullable|string|max:50',
      'user_id'    => 'nullable|integer|min:1',
      'date_debut' => 'nullable|date',
      'date_fin'   => 'nullable|date|after_or_equal:date_debut',
    ];
  }
}
