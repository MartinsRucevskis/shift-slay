<?php

declare(strict_types=1);

use App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\AddResponseAsParam;
use App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\AddResponseAsParamWhenCaller;
use App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\AddTestAttributeForTests;
use App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\ChainResponseCodes;
use App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\ExampleToTestWithDocs;
use App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\RefactorGetResponse;
use App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\RefactorGrabFromDatabase;
use App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\RefactorMockAccess;
use App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\RefactorMockCreation;
use App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\RenameApiTesterMethod;
use App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\ReplaceApiTesterForOutsideMethodCalls;
use App\Shift\Rector\CodeceptionToLaravel\RulesFirstRun\ResponseCodesToAsserts;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->rule(RenameApiTesterMethod::class);
    $rectorConfig->rule(RefactorMockAccess::class);
    $rectorConfig->rule(RefactorGetResponse::class);
    $rectorConfig->rule(ChainResponseCodes::class);
    $rectorConfig->rule(ExampleToTestWithDocs::class);
    $rectorConfig->rule(ResponseCodesToAsserts::class);
    $rectorConfig->rule(RefactorMockCreation::class);
    $rectorConfig->rule(AddTestAttributeForTests::class);
    $rectorConfig->rule(RefactorGrabFromDatabase::class);
    $rectorConfig->rule(ReplaceApiTesterForOutsideMethodCalls::class);
    //    $rectorConfig->rule(AddResponseAsParam::class); Need to improve
    //    $rectorConfig->rule(AddResponseAsParamWhenCaller::class) Add to second run and check if method has it as a caller

    $rectorConfig->importNames();
    $rectorConfig->removeUnusedImports();
};
