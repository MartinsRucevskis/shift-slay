<?php

namespace App\Shift\Rector\Shifter;

use App\Shift\EnhancedSnippetAnalyzer;
use App\Shift\Rector\Shifter\RuleGenerators\RuleGeneratorFactory;
use App\Shift\SnippetAnalyzers;
use Illuminate\Support\Facades\File;
use PHPSemVerChecker\Analyzer\Analyzer;
use PHPSemVerChecker\Filter\SourceFilter;
use PHPSemVerChecker\Finder\Finder;
use PHPSemVerChecker\Report\Report;
use PHPSemVerChecker\Scanner\ProgressScanner;
use PHPSemVerChecker\Scanner\Scanner;
use SplFileInfo;
use Symfony\Component\Console\Output\ConsoleOutput;

class BreakingChangeFinder
{
    public function findBreakingChanges($oldPackagePath, $newPackagePath): array
    {

//        require_once base_path('/semver/vendor/autoload.php');
//        (new RefactorAnalyzer($oldPackagePath, $newPackagePath))->analyze();
        (new EnhancedSnippetAnalyzer($oldPackagePath, $newPackagePath))->analyze(__DIR__ . '/changed.json');
        (new \App\Shift\RectorRuleGenerator())->generateRulesFromChanged(__DIR__ . '/changed.json', __DIR__ . '/Changeds');
        return [];
        require_once base_path('/semver/vendor/autoload.php');
        $finder = new Finder();
        $scannerBefore = new Scanner();
        $scannerAfter = new Scanner();


        $sourceBefore = $finder->findFromString($oldPackagePath, null, 'tests');
        $sourceAfter = $finder->findFromString($newPackagePath, null, 'tests');
        $progress = new ProgressScanner(new ConsoleOutput());
        $progress->addJob($oldPackagePath, $sourceBefore, $scannerBefore);
        $progress->addJob($newPackagePath, $sourceAfter, $scannerAfter);
        $progress->runJobs();
        $sourceFilter = new SourceFilter();
        $identicalCount = $sourceFilter->filter($sourceBefore, $sourceAfter);


        $registryBefore = $scannerBefore->getRegistry();
        $registryAfter = $scannerAfter->getRegistry();

        $analyzer = new Analyzer();
        $report = $analyzer->analyze($registryBefore, $registryAfter);
        $this->generateRules($report);
        $changes = $this->getFileChanges($oldPackagePath, $newPackagePath);
        return [];
    }

    /**
     * @param $oldPackagePath
     * @param $newPackagePath
     * @return array{matching: SplFileInfo[], new: SplFileInfo[], removed: SplFileInfo[]}
     */
    private function getFileChanges($oldPackagePath, $newPackagePath): array
    {
        $newPackageFiles = collect(File::allFiles($newPackagePath))
            ->filter(fn (SplFileInfo $file) => !$file->isDir() && $file->isReadable() && $file->getExtension() === 'php')
            ->keyBy(fn($file) => $file->getFilename());

        $oldPackageFiles = collect(File::allFiles($oldPackagePath))
            ->filter(fn (SplFileInfo $file) => !$file->isDir()&& $file->isReadable() && $file->getExtension() === 'php')
            ->keyBy(fn($file) => $file->getFilename());

        $matchingFiles = [];
        $newFiles = [];
        foreach ($newPackageFiles as $fileName => $newPackageFile) {
            if (isset($oldPackageFiles[$fileName])) {
                $matchingFiles[$fileName] = ['new' => $newPackageFile, 'old' => $oldPackageFiles[$fileName]];
            } else {
                $newFiles[$fileName] = $newPackageFile;
            }
        }

        $oldFiles = [];
        foreach ($oldPackageFiles as $fileName => $oldPackageFile) {
            if (!isset($matchingFiles[$fileName]) && !isset($newFiles[$fileName])) {
                $oldFiles[$fileName] = $oldPackageFile;
            }
        }

        return [
            'matching' => $matchingFiles,
            'new' => $newFiles,
            'old' => $oldFiles
        ];
    }

    private function generateRules(Report $report)
    {
        $differences = $report->getDifferences();
        $this->generateClassRules($differences['class']);
    }

    private function generateClassRules(array $classDiffs = []): void
    {
        $ruleGeneratorFactory = new RuleGeneratorFactory();
        foreach ($classDiffs as $classDiffSeverity) {
            foreach ($classDiffSeverity as $classDiff) {
                $ruleGenerator = $ruleGeneratorFactory->createRuleGenerator($classDiff);
                if (isset($ruleGenerator)){
                    $ruleGenerator->generateRule();
                }
            }
        }
    }
}
