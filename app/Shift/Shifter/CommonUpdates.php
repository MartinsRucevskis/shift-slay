<?php

declare(strict_types=1);

namespace App\Shift\Shifter;

class CommonUpdates
{
    /**
     * @return mixed[]
     */
    public static function commonChanges(): array
    {
        $command = config('shift.command_name');
        $command = explode(':', (string) $command);
        $command = $command[1];

        return match ($command) {
            'Lumen8ToLaravel8' => [
                'helpers' => [
                    'config' => [
                        'params' => [
                            "'app.local_time_zone'" => "'app.locale'",
                        ],
                    ],
                ],
            ],
            default => [

            ]
        };
    }
}
