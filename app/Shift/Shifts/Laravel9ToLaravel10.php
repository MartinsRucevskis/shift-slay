<?php

namespace App\Shift\Shifts;

use Symfony\Component\Process\Process;

class Laravel9ToLaravel10 implements BaseShift
{
    public function run(string $directory): void
    {
        $this->runRector($directory);
        $this->fixConfigApp($directory);
    }

    private function runRector(string $directory): void
    {
        $directories = scandir($directory);
        if(!$directories){
            throw new \Exception('Couldn\'t open the specified directory : '. $directory);
        }
        $directories = array_filter($directories, fn ($dir) => ! str_contains($dir, '.') && ! str_contains($dir, 'vendor') && is_dir($dir));
        foreach ($directories as &$dir) {
            $dir = $directory.'\\'.$dir;
        }
        unset($dir);
        $process = new Process(['vendor/bin/rector', 'process', ...$directories, '--config', app_path('\Shift\Rector\Laravel10\rector.php'), '--debug'], null, null, null, 160);
        $process->run();
        echo $process->getOutput();
    }

    private function fixConfigApp(string $directory): void
    {
        $appConfig = file_get_contents($directory.'/config/app.php');
        $appConfig = preg_replace('/\'providers\' => \[[\s\S]+Illuminate\\\\View\\\\ViewServiceProvider::class,(.+?])/ms', '\'providers\' => \Illuminate\Support\ServiceProvider::defaultProviders()->merge([$1)->toArray()', $appConfig);

        $appConfig = preg_replace('#\'aliases\' => \[[\s\S]+\'View\' => Illuminate\\\\Support\\\\Facades\\\\View::class,(.+?])#ms', '\Illuminate\Support\Facades\Facade::defaultAliases()->merge([$1)->toArray()', $appConfig);

        file_put_contents($directory.'/config/app.php', $appConfig);
    }
}
