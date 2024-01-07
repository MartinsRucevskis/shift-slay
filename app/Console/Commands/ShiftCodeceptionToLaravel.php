<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Shift\Shifts\CodeceptionToLaravelTests;
use Illuminate\Console\Command;

class ShiftCodeceptionToLaravel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shift:CodeceptionToLaravel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for automatically shifting Codeception test to Laravel tests';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        echo "I'm starting to shift, SLAYYY!".PHP_EOL;
        config(['shift.command_name' => 'shift:CodeceptionToPhpUnit']);
        (new CodeceptionToLaravelTests)->run(config('shift.project_path'));
        echo "I'm done!!".PHP_EOL;
    }
}
