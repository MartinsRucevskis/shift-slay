<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelLevelSetList;

return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->sets([
        LaravelLevelSetList::UP_TO_LARAVEL_90,
    ]);
};
