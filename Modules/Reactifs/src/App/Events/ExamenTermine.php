<?php

namespace Modules\Reactifs\App\Events; 

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExamenTermine
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $examenId,
        public readonly int $examenTypeId,
        public readonly int $userId,
    ) {}
}