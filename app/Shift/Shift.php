<?php

namespace App\Shift;

use App\Shift\FileMover\FixFile;
use App\Shift\Objects\FileClass;

class Shift
{
    /**
     * @var string[]
     */
    private array $overLappingFiles = [];
    private array $filesToOverwrite = [
        'web/tests/_bootstrap.php',
        'web/artisan',
        'web/public/index.php'
    ];
    public function run($directory): void
    {
        $this->addLaravelFiles('C:\Users\martins.rucevskis\plainLaravel8' ,$directory);
        $this->fixConfig($directory);
        $this->fixFiles($directory);
    }

    private function addLaravelFiles($sourceDirectory, $destinationDirectory){
        $filesAndDirectories = scandir($sourceDirectory);

        unset($filesAndDirectories[array_search('.', $filesAndDirectories, true)]);
        unset($filesAndDirectories[array_search('..', $filesAndDirectories, true)]);

        if (count($filesAndDirectories) < 1)
            return;

        foreach ($filesAndDirectories as $fileOrDirectory) {
            $sourcePath = $sourceDirectory . '/' . $fileOrDirectory;
            $destinationPath = $destinationDirectory . '/' . $fileOrDirectory;

            if (is_dir($sourcePath)) {
                if (!is_dir($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }
                $this->addLaravelFiles($sourcePath, $destinationPath);
            } elseif(!file_exists($destinationPath)){
                copy($sourcePath, $destinationPath);
            } elseif (file_exists($destinationPath)){
                $this->overLappingFiles[] = $destinationPath;
            }
        }
    }


    private function fixFiles($directory){
        $filesAndDirectories = scandir($directory);

        unset($filesAndDirectories[array_search('.', $filesAndDirectories, true)]);
        unset($filesAndDirectories[array_search('..', $filesAndDirectories, true)]);

        if (count($filesAndDirectories) < 1)
            return;

        foreach ($filesAndDirectories as $fileOrDirectory) {
            $fullPath = $directory . '/' . $fileOrDirectory;
            if (is_dir($fullPath)) {
                if (str_contains($fileOrDirectory, 'vendor')) {
                    continue;
                }
                $this->fixFiles($fullPath);
            } elseif (str_contains($fileOrDirectory, '.php') && !str_contains($fileOrDirectory, 'autoload.php')) {
                try {
                    (new FixFile(
                        new FileClass($fullPath)
                    ))->fix();
                } catch (\Exception $exception){
                    echo $exception->getMessage().PHP_EOL;
                }
            };
        }
    }

    private function fixConfig($directory){
//        $appBoostrap = file_get_contents();
    }


}
