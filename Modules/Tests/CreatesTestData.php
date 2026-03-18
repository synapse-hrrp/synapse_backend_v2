<?php

namespace Modules\Tests;

trait CreatesTestData
{
    protected int $patientId;
    protected int $registreId;

    protected function creerPatientEtRegistre(): void
    {
        $personneId = \DB::table('t_personnes')->insertGetId([
            'nom'        => 'Test',
            'prenom'     => 'Patient',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->patientId = \DB::table('t_patients')->insertGetId([
            'personne_id' => $personneId,
            'nip'         => 'NIP-' . uniqid(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $this->registreId = \DB::table('reception_registre_journalier')->insertGetId([
            'id_patient'  => $this->patientId,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}