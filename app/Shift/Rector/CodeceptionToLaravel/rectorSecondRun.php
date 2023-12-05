<?php

declare(strict_types=1);

use App\Shift\Rector\CodeceptionToLaravel\RulesSecondRun\RefactorApiTesterToTestCase;
use App\Shift\Rector\CodeceptionToLaravel\RulesSecondRun\RefactorClassToPhpUnitTestCase;
use App\Shift\Rector\CodeceptionToLaravel\RulesSecondRun\RemoveApiTesterParams;
use App\Shift\Rector\CodeceptionToLaravel\RulesSecondRun\ReplaceApiTesterObject;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->rule(ReplaceApiTesterObject::class);
    $rectorConfig->rule(RefactorClassToPhpUnitTestCase::class);
    $rectorConfig->rule(RefactorApiTesterToTestCase::class);
    $rectorConfig->rule(RemoveApiTesterParams::class);
    $rectorConfig->sets([
        \Rector\Set\ValueObject\SetList::DEAD_CODE,
    ]);
    $rectorConfig->importNames();
};
