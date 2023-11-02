<?php

namespace Tests\Feature\FixProject\Resources\TestProject\app;

use Tests\Feature\FixProject\Resources\TestProject\vendor\SlayPackage\SlayPackage;

class TestController extends Controller
{

    public function endpoint(): void
    {
        $supportPackage = $this->variable->someNewFunction();
        $randomVariable = new SlayPackage($this->testString(
            $this->additionalString(),
            function () use ($supportPackage){
                return fn() =>
                $this
                    ->additionalString().
                    $supportPackage
                        ->randomStringNew();
            }
        ));
    }

    private function testString(string $additionalString, callable $testCallback): string
    {
        return 'randomString' . $additionalString;
    }
}
