<?php

declare(strict_types=1);

use App\Shift\Rector\Laravel10\Rules\DepreciatedSetFacility;
use App\Shift\Rector\Laravel10\Rules\FixMonolog;
use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelLevelSetList;

return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->ruleWithConfiguration(\Rector\Renaming\Rector\Name\RenameClassRector::class, [
        'Fideloper\Proxy\TrustProxies' => 'Illuminate\\Http\\Middleware\\TrustProxies',
    ]);
    $rectorConfig->rule(FixMonolog::class);
    $rectorConfig->rule(DepreciatedSetFacility::class);

    $rectorConfig->sets([
        LaravelLevelSetList::UP_TO_LARAVEL_100,
    ]);
};
