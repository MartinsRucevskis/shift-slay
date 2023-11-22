<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use App\Shift\Rector\Laravel10\Rules\FixMonolog;

return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->ruleWithConfiguration(\Rector\Renaming\Rector\Name\RenameClassRector::class, ['Fideloper\Proxy\TrustProxies' => 'Illuminate\\Http\\Middleware\\TrustProxies'] );
    // register a single rule
    $rectorConfig->rule(FixMonolog::class);

    // define sets of rules
    $rectorConfig->sets([
        \RectorLaravel\Set\LaravelLevelSetList::UP_TO_LARAVEL_100
    ]);
    $rectorConfig->importNames();
};
