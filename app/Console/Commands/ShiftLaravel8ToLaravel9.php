<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Shift\Shifts\Laravel8ToLaravel9;
use Illuminate\Console\Command;

class ShiftLaravel8ToLaravel9 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shift:Laravel8ToLaravel9';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for automatically shifting laravel 8 to laravel 9';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        echo "I'm starting to shift, SLAYYY!".PHP_EOL;
        config(['shift.command_name' => 'shift:Laravel8ToLaravel9']);
        (new Laravel8ToLaravel9())->run(config('shift.project_path'));
        echo "I'm done!!".PHP_EOL;
    }
}
