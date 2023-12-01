<?php

namespace App\Shift\Shifts;

use Symfony\Component\Process\Process;

class Laravel8ToLaravel9 implements BaseShift
{
    public function run(string $directory): void
    {
        $this->runRector($directory);
    }

    private function runRector(string $directory): void
    {
        $directories = scandir($directory);
        if (! $directories) {
            throw new \Exception('Couldn\'t open the specified directory : '.$directory);
        }
        $directories = array_filter($directories, fn ($dir) => ! str_contains($dir, '.') && ! str_contains($dir, 'vendor') && is_dir($dir));
        foreach ($directories as &$dir) {
            $dir = $directory.'\\'.$dir;
        }
        unset($dir);
        $process = new Process(['vendor/bin/rector', 'process', ...$directories, '--config', app_path('\Shift\Rector\Laravel9\rector.php'), '--debug'], null, null, null, 160);
        $process->run();
        echo $process->getOutput();
    }
}
