<?php

namespace Modules\Imagerie\App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagerieServiceMapping extends Model
{
    protected $table = 'imagerie_service_mappings';

    protected $fillable = [
        'billable_service_id',
        'imagerie_type_id',
    ];

    protected $casts = [
        'billable_service_id' => 'integer',
        'imagerie_type_id'    => 'integer',
    ];
}