<?php
namespace Tests\Feature\FixProject\Resources\TestProject\app;
use Tests\Feature\FixProject\Resources\TestProject\vendor\SlayPackage\SlayPackage;
use Illuminate\Routing\Controller;

class Controller
{

    public SlayPackage $variable;

    protected function additionalString(): string{
        return 'additionalString';
    }
}
