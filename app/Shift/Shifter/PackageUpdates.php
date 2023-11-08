<?php

namespace App\Shift\Shifter;

class PackageUpdates
{
    /**
     * @return mixed[]
     */
    public static function methodChanges(): array
    {
        $command = config('shift.command_name');
        $command = explode(':', $command);
        $command = $command[1];

        return match ($command) {
            'Lumen8ToLaravel8' => [
                'Tests\Feature\FixProject\Resources\TestProject\vendor\SlayPackage\SlayPackage' => [
                    'methods' => [
                        'someFunction' => 'someNewFunction',
                    ],
                ],
                'Tests\Feature\FixProject\Resources\TestProject\vendor\SlayPackage\Support' => [
                    'methods' => [
                        'randomString' => 'randomStringNew',
                    ],
                ],
                'Laravel\Lumen\Routing\Controller' => [
                    'replaceWith' => 'Illuminate\Routing\Controller',
                ],
                'Laravel\Lumen\Exceptions\Handler' => [
                    'replaceWith' => 'Illuminate\Foundation\Exceptions\Handler',

                ],
                'Pearl\RequestValidate\RequestAbstract' => [
                    'replaceWith' => 'Illuminate\Foundation\Http\FormRequest',

                ],
                'Dingo\Api\Provider\LumenServiceProvider' => [
                    'replaceWith' => 'Dingo\Api\Provider\LaravelServiceProvider',

                ],
                'Laravel\Lumen\Console\Kernel' => [
                    'replaceWith' => 'Illuminate\Foundation\Console\Kernel',
                ],
            ],
            default => [

            ]
        };
    }
}
