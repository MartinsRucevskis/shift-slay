<?php

declare(strict_types=1);

use App\Shift\Rector\ImproveTestStyling\Rules\ChainJsonAsserts;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->rule(ChainJsonAsserts::class);

    $rectorConfig->sets([
        \Rector\Set\ValueObject\SetList::PHP_83,
    ]);
};
