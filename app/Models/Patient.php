<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 't_patients';
    protected $guarded = [];
    protected $appends = ['display_code'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($patient) {
            if (!empty($patient->nip)) return;

            $year = (int) now()->format('Y');

            // ✅ Séquence atomique par année (anti-collision)
            $nextNumber = DB::transaction(function () use ($year) {
                $row = DB::table('t_nip_sequences')
                    ->where('annee', $year)
                    ->lockForUpdate()
                    ->first();

                if (!$row) {
                    DB::table('t_nip_sequences')->insert([
                        'annee' => $year,
                        'dernier_numero' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $row = DB::table('t_nip_sequences')
                        ->where('annee', $year)
                        ->lockForUpdate()
                        ->first();
                }

                $next = ((int) $row->dernier_numero) + 1;

                DB::table('t_nip_sequences')
                    ->where('annee', $year)
                    ->update([
                        'dernier_numero' => $next,
                        'updated_at' => now(),
                    ]);

                return $next;
            });

            $patient->nip = sprintf('NIP-%d-%05d', $year, $nextNumber);
        });
    }

    public function personne(): BelongsTo
    {
        return $this->belongsTo(Personne::class);
    }

    public function getDisplayCodeAttribute(): string
    {
        return $this->nip ?? ('PAT-' . ($this->id ?? 'X'));
    }
}
