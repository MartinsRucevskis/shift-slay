<?php

namespace Tests\Feature\FixProject\Resources\TestProject\vendor\SlayPackage;

class SlayPackage
{
    public function __construct(public string $randomString = 'default')
    {
    }

    public function someFunction(): Support{
        return new Support();
    }
    public function someNewFunction(): Support{
        return new Support();
    }
    public function someUpdatedFunction(): string{
        return 'calledNew';
    }
}
