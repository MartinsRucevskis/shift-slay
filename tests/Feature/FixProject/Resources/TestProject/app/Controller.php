<?php

namespace Tests\Feature\FixProject\Resources\TestProject\app;

use Laravel\Lumen\Routing\Controller;
use Tests\Feature\FixProject\Resources\TestProject\vendor\SlayPackage\SlayPackage;

class Controller
{
    public SlayPackage $variable;

    protected function additionalString(): string
    {
        return 'additionalString';
    }
}
