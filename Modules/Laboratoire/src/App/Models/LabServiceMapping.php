<?php

namespace Modules\Laboratoire\App\Models;

use Illuminate\Database\Eloquent\Model;

class LabServiceMapping extends Model
{
    protected $table = 'lab_service_mappings';

    protected $fillable = [
        'billable_service_id',
        'examen_type_id',
    ];
}