<?php

namespace Tests\Feature\FixProject;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class FixProjectTest extends TestCase
{
    protected function setUp(): void
    {
        $this->copyProject();
        parent::setUp();
    }

    /**
     * A basic test example.
     */
    public function test_migrate_project(): void
    {
        $this->markTestIncomplete('Need to traverse whole arrays, provide files for github actions');
        Artisan::call('shift:Lumen8ToLaravel8');
        $this->assertEquals(
            file_get_contents(__DIR__.'/Resources/TestProject/app/TestController.php'),
            file_get_contents(__DIR__.'/Resources/TestProjectFixed/TestProject/app/TestController.txt')
        );
    }

    private function copyProject()
    {
        $this->deleteAll(__DIR__.'/Resources/TestProject/');
        $this->recurseCopy(__DIR__.'/Resources/TestProjectCopy/', __DIR__.'/Resources/TestProject/');

    }

    public function recurseCopy(
        string $sourceDirectory,
        string $destinationDirectory
    ): void {
        $directory = opendir($sourceDirectory);
        if ($directory === false) {
            throw new Exception("Unable to open directory: $sourceDirectory");
        }

        while (false !== ($file = readdir($directory))) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (is_dir("$sourceDirectory/$file") === true) {
                if (! is_dir("$destinationDirectory/$file")) {
                    mkdir("$destinationDirectory/$file", 0755, true);
                }
                $this->recurseCopy("$sourceDirectory/$file", "$destinationDirectory/$file");
            } else {
                $fileNew = str_replace('.txt', '.php', $file);
                if (! file_exists("$destinationDirectory/$fileNew")) {
                    copy("$sourceDirectory/$file", "$destinationDirectory/$fileNew");
                }
            }
        }

        closedir($directory);
    }

    public function deleteAll($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir.'/'.$object) == 'dir') {
                        $this->deleteAll($dir.'/'.$object);
                    } else {
                        unlink($dir.'/'.$object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }
}
