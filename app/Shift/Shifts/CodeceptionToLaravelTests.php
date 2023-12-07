<?php

declare(strict_types=1);

namespace App\Shift\Shifts;

use Exception;
use Illuminate\Support\Facades\File;
use PHPSQLParser\PHPSQLParser;
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
        $this->fixTestFileFormatting('C:\Users\martins.rucevskis\projects\product-server\web\tests\\');
        $this->dumpToSeeders();
    }

    private function addTestFiles(string $app_path, string $directory)
    {
        File::copyDirectory($app_path, $directory.'/tests');
    }

    private function runRector($directory)
    {
        $process = new Process(['vendor/bin/rector', 'process', $directory.'\tests\\', '--config', app_path('\Shift\Rector\CodeceptionToLaravel\rectorFirstRun.php'), '--debug'], null, null, null, 300);
        $process->run();
        echo 'Rector Changes from First run : '.PHP_EOL;
        echo $process->getOutput();

        $process = new Process(['vendor/bin/rector', 'process', $directory.'\tests\\', '--config', app_path('\Shift\Rector\CodeceptionToLaravel\rectorSecondRun.php'), '--debug'], null, null, null, 300);
        $process->run();
        echo 'Rector Changes from Second run : '.PHP_EOL;
        echo $process->getOutput();

        $process = new Process(['vendor/bin/rector', 'process', $directory.'\tests\\', '--config', app_path('\Shift\Rector\CodeceptionToLaravel\rectorThirdRun.php'), '--debug'], null, null, null, 300);
        $process->run();
        echo 'Rector Changes from Third run : '.PHP_EOL;
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

            if (is_dir($sourceDirectory.$file) === true) {
                if (str_ends_with($sourceDirectory.$file, 'tests\api')) {
                    shell_exec('git -C '.$sourceDirectory.' mv '.$sourceDirectory.$file.' '.$sourceDirectory.'Feature');
                    $file = 'Feature';
                }
                if (str_ends_with($sourceDirectory.$file, 'unit')) {
                    shell_exec('git -C '.$sourceDirectory.'../'.' mv '.$sourceDirectory.$file.' '.$sourceDirectory.'tempName');
                    $file = 'tempName';
                    shell_exec('git -C '.$sourceDirectory.'../'.' mv '.$sourceDirectory.$file.' '.$sourceDirectory.'Unit');
                    $file = 'Unit';
                }
                $this->fixTestFileFormatting($sourceDirectory.$file.'\\');
            } else {
                if (str_ends_with($file, 'Cest.php')) {
                    $renamedFile = str_replace('Cest.php', 'Test.php', $file);
                    shell_exec('git -C '.$sourceDirectory.' mv '.$sourceDirectory.$file.' '.$sourceDirectory.$renamedFile);
                }
            }
        }
    }

    private function dumpToSeeders()
    {
        $sqlDump = file_get_contents('C:\Users\martins.rucevskis\projects\product-server\web\tests\_data\dump.sql');

        $queries = explode(';', $sqlDump);
        $kkadiItemi = [];
        foreach ($queries as $query) {
            $query = trim($query);
            if (stripos($query, 'INSERT') === 0) {
                $parser = new PHPSQLParser($query);
                foreach ($parser->parsed['INSERT'] as $item) {
                    if ($item['expr_type'] === 'table') {
                        $table = str_replace('`', '', $item['table']);
                    }
                    if ($item['expr_type'] === 'column-list') {
                        $columns = [];
                        foreach ($item['sub_tree'] as $name) {
                            $columns[] = str_replace('`', '', $name['base_expr']);
                        }
                    }
                }
                foreach ($parser->parsed['VALUES'] as $item) {
                    $data = [];
                    foreach ($item['data'] as $key => $dataItem) {
                        $data[$columns[$key]] = str_replace('`', '', $dataItem['base_expr']);
                    }
                    $kkadiItemi[$table][] = $data;
                }
            }

        }
        $seeders = [];
        foreach ($kkadiItemi as $table => $records) {
            $this->createSeeder($table, $records);
            $seeders[] = $this->tableToModel($table).'TableSeeder';
        }
        $this->createTestSeeder($seeders);
    }

    private function tableToModel($tableName)
    {
        $studlyCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName)));

        return ucfirst($studlyCase);
    }

    private function createSeeder(string $table, array $records)
    {
        $seederPath = 'C:\Users\martins.rucevskis\projects\product-server\web\database\seeders\\'.$this->tableToModel($table).'TableSeeder.php';
        copy('C:\Users\martins.rucevskis\PhpstormProjects\shift-slay\app\Shift\ExampleFiles\ExampleSeeder.php', $seederPath);
        $seeder = file_get_contents($seederPath);
        $seeder = str_replace('ExampleSeeder', $this->tableToModel($table).'TableSeeder', $seeder);
        $seeder = str_replace('example_table', $table, $seeder);
        $seeder = str_replace('\'valueaarray\'', $this->recordsAsArrayString($records, $table), $seeder);
        file_put_contents($seederPath, $seeder);
    }

    private function recordsAsArrayString(array $records, string $table)
    {
        $recordString = '';
        $recordCount = count($records[0]) ?? null;
        foreach ($records as $key => $record) {
            if ($key !== 0 && array_keys($records[$key-1]) !== array_keys($record)) {
                $recordString .= '            '.PHP_EOL.'        ]);'.PHP_EOL;
                $recordString .= '            DB::table(\''.$table.'\')->insert(['.PHP_EOL.'                ['.PHP_EOL;
            } else{
                $recordString .= '            ['.PHP_EOL;
            }
            foreach ($record as $column => $value) {
                $recordString .= '                \''.$column.'\' => '.$value.','.PHP_EOL;
            }
            $recordString .= '            ],'.PHP_EOL;
        }

        return $recordString.'        ';
    }

    private function createTestSeeder(array $seeders)
    {
        $testSeederPath = 'C:\Users\martins.rucevskis\projects\product-server\web\database\seeders\TestSeeder.php';
        copy('C:\Users\martins.rucevskis\PhpstormProjects\shift-slay\app\Shift\ExampleFiles\ExampleTestSeeder.php',
            $testSeederPath);
        $seeder = file_get_contents($testSeederPath);
        $seeder = str_replace('\'seeders\'',
            implode('::class,'.PHP_EOL.'            ', $seeders).'::class,'.PHP_EOL.'        ', $seeder);
        file_put_contents($testSeederPath, $seeder);

    }

    private function seedersAsArray()
    {
    }
}
