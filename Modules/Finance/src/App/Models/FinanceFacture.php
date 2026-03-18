<?php

namespace Modules\Finance\App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceFacture extends Model
{
    protected $table = 'v_finance_factures';
    public $timestamps = false;

    // la vue n'a pas d'ID auto; on empêche les écritures
    public $incrementing = false;
    protected $guarded = [];

    protected static function booted()
    {
        static::creating(fn () => false);
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }
}