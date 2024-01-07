<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Shift\Shifts\Laravel9ToLaravel10;
use Illuminate\Console\Command;

class ShiftLaravel9ToLaravel10 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shift:Laravel9ToLaravel10';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for automatically shifting laravel 9 to laravel 10';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        echo "I'm starting to shift, SLAYYY!".PHP_EOL;
        config(['shift.command_name' => 'shift:Laravel9ToLaravel10']);
        (new Laravel9ToLaravel10())->run(config('shift.project_path'));
        echo "I'm done!!".PHP_EOL;
    }
}
