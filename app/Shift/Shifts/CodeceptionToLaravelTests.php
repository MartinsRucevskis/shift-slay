<?php

declare(strict_types=1);

namespace App\Shift\Shifts;

use Exception;
use Symfony\Component\Process\Process;

class CodeceptionToLaravelTests implements BaseShift
{
    /**
     * @var string[]
     */
    public array $overLappingFiles = [];

    public function run(string $directory): void
    {
        $this->addTestFiles(app_path('/Shift/LaravelShiftFiles/LaravelTests/'), $directory);
        $this->runRector($directory);
        $this->fixTestFileFormatting($directory);
    }

    private function addLaravelFiles(string $sourceDirectory, string $destinationDirectory): void
    {
        $filesAndDirectories = scandir($sourceDirectory) ?: throw new Exception('Failed to scan directory');
        unset($filesAndDirectories[array_search('.', $filesAndDirectories, true)]);
        unset($filesAndDirectories[array_search('..', $filesAndDirectories, true)]);

        if (count($filesAndDirectories) < 1) {
            return;
        }

        foreach ($filesAndDirectories as $fileOrDirectory) {
            $sourcePath = $sourceDirectory.'/'.$fileOrDirectory;
            $destinationPath = $destinationDirectory.'/'.$fileOrDirectory;

            if (is_dir($sourcePath)) {
                if (! is_dir($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                $this->addLaravelFiles($sourcePath, $destinationPath);
            } elseif (! file_exists($destinationPath) || str_contains($destinationPath, 'config/app.php') || str_contains($destinationPath, 'index.php')) {
                copy($sourcePath, $destinationPath);
            } elseif (file_exists($destinationPath)) {
                $this->overLappingFiles[] = $destinationPath;
            }
        }
    }

    private function addTestFiles(string $app_path, string $directory)
    {
    }

    private function runRector($directory)
    {
        $process = new Process(['vendor/bin/rector', 'process', 'C:\Users\martins.rucevskis\projects\product-server\web\tests\\', '--config', app_path('\Shift\Rector\CodeceptionToLaravel\rectorFirstRun.php'), '--xdebug', '--debug', '--dry-run'], null, null, null, 300);
        $process->run();
        echo 'Rector Changes from First run : '.PHP_EOL;
        echo $process->getOutput();

        $process = new Process(['vendor/bin/rector', 'process', 'C:\Users\martins.rucevskis\projects\product-server\web\tests\\', '--config', app_path('\Shift\Rector\CodeceptionToLaravel\rectorSecondRun.php'), '--xdebug', '--debug', '--dry-run'], null, null, null, 300);
        $process->run();
        echo 'Rector Changes from Second run : '.PHP_EOL;
        echo $process->getOutput();
    }

    private function fixTestFileFormatting(string $sourceDirectory)
    {
        $directory = opendir($sourceDirectory);
        if ($directory === false) {
            throw new Exception("Unable to open directory: $sourceDirectory");
        }

        while (false !== ($file = readdir($directory))) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (is_dir("$sourceDirectory/$file") === true) {
                $this->fixTestFileFormatting("$sourceDirectory/$file");
            } else {
                if (str_ends_with($file, 'Cest.php')) {
                    rename($sourceDirectory.'/'.$file, str_replace('Cest.php', 'Test.php', $file));
                }
            }
        }
    }

    private function fixTestAnnotations(string $string)
    {
    }
}
