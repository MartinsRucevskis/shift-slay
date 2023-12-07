<?php

declare(strict_types=1);

use App\Shift\Rector\CodeceptionToLaravel\RulesThirdRun\RemoveSelfFromClosure;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->sets([
        \Rector\Set\ValueObject\SetList::DEAD_CODE,
    ]);
    $rectorConfig->rule(RemoveSelfFromClosure::class);
    $rectorConfig->rule(\App\Shift\Rector\CodeceptionToLaravel\RulesThirdRun\RemoveApiTesterFromCallBack::class);
    $rectorConfig->importNames();
    $rectorConfig->removeUnusedImports();
};
