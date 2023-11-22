<?php

namespace App\Shift\Shifts;

use Symfony\Component\Process\Process;

class Laravel9ToLaravel10 implements BaseShift
{

    public function run(string $directory): void
    {
        $this->runRector($directory);
    }

    private function runRector(string $directory): void{
        $directories = scandir($directory);
        $directories = array_filter($directories, fn($dir)=>!str_contains($dir, '.') && !str_contains($dir, 'vendor') && is_dir($dir));
        foreach ($directories as &$dir) {
            $dir = $directory . '\\' . $dir;
        }
        unset($dir);
        $process = new Process(['vendor/bin/rector', 'process', ...$directories, '--config', app_path('\Shift\Rector\Laravel10\rector.php'), '--dry-run']);
        $process->run();
        echo $process->getOutput();
    }
}
