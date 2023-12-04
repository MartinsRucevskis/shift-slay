<?php

declare(strict_types=1);

use App\Shift\Rector\CodeceptionToLaravel\Rules\ExampleToTestWithDocs;
use App\Shift\Rector\CodeceptionToLaravel\Rules\RefactorGetResponse;
use App\Shift\Rector\CodeceptionToLaravel\Rules\RenameApiTesterMethod;
use App\Shift\Rector\CodeceptionToLaravel\Rules\ChainResponseCodes;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {

    //Order is important, ChainReponseCodes Should be ran first, since this will change method tree and look for original nodes.
    $rectorConfig->rule(RenameApiTesterMethod::class);
    $rectorConfig->rule(RefactorGetResponse::class);
    $rectorConfig->rule(ChainResponseCodes::class);
    $rectorConfig->rule(ExampleToTestWithDocs::class);
    $rectorConfig->importNames();
//    $rectorConfig->rules([
//        ExtendTestCase::class,
//
//    ])
};
