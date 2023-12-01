<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelLevelSetList;

return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->ruleWithConfiguration(\Rector\Renaming\Rector\Name\RenameClassRector::class, [
        'Fideloper\Proxy\TrustProxies' => 'Illuminate\\Http\\Middleware\\TrustProxies',
        'Laravel\Lumen\Exceptions\Handler' => 'Illuminate\Foundation\Exceptions\Handler',
        'Pearl\RequestValidate\RequestAbstract' => 'Illuminate\Foundation\Http\FormRequest',
        'Dingo\Api\Provider\LumenServiceProvider' => 'Dingo\Api\Provider\LaravelServiceProvider',
        'Laravel\Lumen\Console\Kernel' => 'Illuminate\Foundation\Console\Kernel',
        'Laravel\Lumen\Routing\Controller' => 'Illuminate\Routing\Controller',
        'Laravel\Lumen\Providers\EventServiceProvider' => 'Illuminate\Foundation\Support\Providers\EventServiceProvider',
    ]);
    $rectorConfig->removeUnusedImports();

    $rectorConfig->sets([
        LaravelLevelSetList::UP_TO_LARAVEL_80,
    ]);
};
