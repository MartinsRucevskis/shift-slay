<?php

declare(strict_types=1);

namespace App\Shift\Shifts;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use PHPSQLParser\PHPSQLParser;
use Symfony\Component\Process\Process;

class CodeceptionToLaravelTests implements BaseShift
{
    /**
     * @var string[]
     */
    public array $overLappingFiles = [];
    public array $nameSpaceChanges = [];

    public function run(string $directory): void
    {
        $testDirectory = $directory. '\tests';
        $this->refactorTests($testDirectory);
        $this->fixTestFileFormatting($testDirectory.'\\');
        $this->dumpToSeeders($directory);
        $this->addTestFiles(app_path('/Shift/LaravelShiftFiles/LaravelTests/'), $testDirectory);
        $this->fixNameSpaces($testDirectory);
    }

    private function addTestFiles(string $app_path, string $directory)
    {
        File::copyDirectory($app_path, $directory);
    }

    private function refactorTests($directory)
    {
        $directory .= '\\';
        $firstRun = $this->runRector(app_path('\Shift\Rector\CodeceptionToLaravel\rectorFirstRun.php'), $directory);
        echo 'Rector Changes from First run : '.PHP_EOL;
        echo $firstRun;

        $secondRun = $this->runRector(app_path('\Shift\Rector\CodeceptionToLaravel\rectorSecondRun.php'), $directory);
        echo 'Rector Changes from First run : '.PHP_EOL;
        echo $secondRun;

        $thirdRun = $this->runRector(app_path('\Shift\Rector\CodeceptionToLaravel\rectorThirdRun.php'), $directory);
        echo 'Rector Changes from Third run : '.PHP_EOL;
        echo $thirdRun;
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
                if (str_ends_with($sourceDirectory.$file, '_support')) {
                    shell_exec('git -C '.$sourceDirectory.'../'.' mv '.$sourceDirectory.$file.' '.$sourceDirectory.'Support');
                    $file = 'Support';
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

    private function dumpToSeeders(string $directory)
    {
        if (!file_exists($directory .'\tests\_data\dump.sql')){
            echo 'Couldn\'t find a dump.sql at ' . $directory . '\tests\_data\'';
            return;
        }
        $sqlDump = file_get_contents($directory .'\tests\_data\dump.sql');

        $queries = explode(';', $sqlDump);
        $tableRecords = [];
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
                    $tableRecords[$table][] = $data;
                }
            }

        }
        $seeders = [];
        foreach ($tableRecords as $table => $records) {
            $this->createSeeder($table, $records, $directory);
            $seeders[] = $this->tableToModel($table).'TableSeeder';
        }
        $this->createTestSeeder($seeders, $directory);
    }

    private function tableToModel($tableName): string
    {
        $studlyCase = str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName)));

        return ucfirst($studlyCase);
    }

    private function createSeeder(string $table, array $records, string $directory)
    {
        $seederPath = $directory.'\database\seeders\\'.$this->tableToModel($table).'TableSeeder.php';
        copy(app_path('\Shift\ExampleFiles\ExampleSeeder.php'), $seederPath);
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

    private function createTestSeeder(array $seeders, string $directory)
    {
        $testSeederPath = $directory . '\database\seeders\TestSeeder.php';
        copy(app_path('\Shift\ExampleFiles\ExampleTestSeeder.php'),
            $testSeederPath);
        $seeder = file_get_contents($testSeederPath);
        $seeder = str_replace('\'seeders\'',
            implode('::class,'.PHP_EOL.'            ', $seeders).'::class,'.PHP_EOL.'        ', $seeder);
        file_put_contents($testSeederPath, $seeder);

    }

    private function fixNameSpaces(string $directory)
    {
        $namespaceFixes = $this->runRector(app_path('\Shift\Rector\CodeceptionToLaravel\rectorFixNamespaces.php'), $directory);
        echo 'Rector Changes from fixing namespaces: '.$directory.PHP_EOL;
        echo $namespaceFixes;
    }

    private function runRector(string $configPath, string $directory): string{
        $process = new Process(['vendor/bin/rector', 'process', $directory, '--config', $configPath, '--debug'], timeout:300);
        $process->run();
        return $process->getOutput();
    }

}
