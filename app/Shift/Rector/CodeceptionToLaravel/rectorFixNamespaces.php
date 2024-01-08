<?php

declare(strict_types=1);

use App\Shift\Rector\Helpers\RenamableClasses;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;

return static function (RectorConfig $rectorConfig): void {

    $renames = (new RenamableClasses(env('SHIFT_PROJECT_PATH').'\tests'))->namespaceRenames();
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class,
        $renames);
    $rectorConfig->importNames();
    $rectorConfig->removeUnusedImports();
};
