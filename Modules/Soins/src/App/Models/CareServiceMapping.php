<?php

namespace Modules\Soins\App\Models;

use Illuminate\Database\Eloquent\Model;

class CareServiceMapping extends Model
{
    protected $table = 'care_service_mappings';
    protected $fillable = ['billable_service_id','care_kind','meta'];
    protected $casts = ['meta' => 'array'];
}