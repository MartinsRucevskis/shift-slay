<?php

declare(strict_types=1);

namespace App\Shift\Shifter;

class PackageUpdates
{
    /**
     * @return mixed[]
     */
    public static function methodChanges(): array
    {
        $command = config('shift.command_name');
        $command = explode(':', (string) $command);
        $command = $command[1];

        return match ($command) {
            'Lumen8ToLaravel8' => [
                \Tests\Feature\FixProject\Resources\TestProject\vendor\SlayPackage\SlayPackage::class => [
                    'methods' => [
                        'someFunction' => 'someNewFunction',
                    ],
                ],
                \Tests\Feature\FixProject\Resources\TestProject\vendor\SlayPackage\Support::class => [
                    'methods' => [
                        'randomString' => 'randomStringNew',
                    ],
                ],
                'Laravel\Lumen\Routing\Controller' => [
                    'replaceWith' => \Illuminate\Routing\Controller::class,
                ],
                'Laravel\Lumen\Exceptions\Handler' => [
                    'replaceWith' => \Illuminate\Foundation\Exceptions\Handler::class,

                ],
                'Pearl\RequestValidate\RequestAbstract' => [
                    'replaceWith' => \Illuminate\Foundation\Http\FormRequest::class,

                ],
                'Dingo\Api\Provider\LumenServiceProvider' => [
                    'replaceWith' => 'Dingo\Api\Provider\LaravelServiceProvider',

                ],
                'Laravel\Lumen\Console\Kernel' => [
                    'replaceWith' => \Illuminate\Foundation\Console\Kernel::class,
                ],
            ],
            default => [

            ]
        };
    }
}
