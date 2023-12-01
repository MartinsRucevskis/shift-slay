<?php

namespace App\Shift\Shifts;

interface BaseShift
{
    public function run(string $directory): void;
}
