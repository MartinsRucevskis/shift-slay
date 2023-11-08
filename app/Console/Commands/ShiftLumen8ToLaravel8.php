<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Shift\Shift;
use Illuminate\Console\Command;

class ShiftLumen8ToLaravel8 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shift:Lumen8ToLaravel8';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for automatically shifting lumen 8 to laravel 8';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        echo 'I\'m starting to shift, SLAYYY!'.PHP_EOL;
        config(['shift.command_name' => 'shift:Lumen8ToLaravel8']);
        (new Shift())->run(config('shift.project_path'));
    }
}
