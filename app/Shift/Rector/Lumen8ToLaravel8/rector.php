<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use App\Shift\Rector\Laravel10\Rules\FixMonolog;

return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->ruleWithConfiguration(\Rector\Renaming\Rector\Name\RenameClassRector::class, [
        'Fideloper\Proxy\TrustProxies' => 'Illuminate\\Http\\Middleware\\TrustProxies',
        'Laravel\Lumen\Exceptions\Handler' => 'Illuminate\Foundation\Exceptions\Handler',
        'Pearl\RequestValidate\RequestAbstract' => 'Illuminate\Foundation\Http\FormRequest',
        'Dingo\Api\Provider\LumenServiceProvider' => 'Dingo\Api\Provider\LaravelServiceProvider',
        'Laravel\Lumen\Console\Kernel' => 'Illuminate\Foundation\Console\Kernel',
        'Laravel\Lumen\Routing\Controller' => 'Illuminate\Routing\Controller'
    ]);

    // register a single rule
//    $rectorConfig->rule(FixMonolog::class);

    // define sets of rules
    $rectorConfig->sets([
        \RectorLaravel\Set\LaravelLevelSetList::UP_TO_LARAVEL_80
    ]);
};
