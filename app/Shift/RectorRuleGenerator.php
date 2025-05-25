<?php

namespace App\Shift;


use App\Shift\Enums\TypeEnums;
use App\Shift\Rector\Shifter\RectorGenerator;
use App\Shift\Rector\Shifter\RuleGenerators\RefChangeGenerator;

class RectorRuleGenerator
{
    public function generateRulesFromChanged(string $aggregatorJsonPath, string $outputDir): void
    {
        $data = json_decode(file_get_contents($aggregatorJsonPath), true);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }
        if (!isset($data) || !is_array($data)) {
            return;
        }
        $generatedCount = 0;
        /** @var array<string, RectorGenerator> $generators */
        $generators = [
            TypeEnums::REF_CHANGE => new RefChangeGenerator(),
        ];
        foreach ($data as $detectedChange) {
            foreach ($detectedChange as $change) {
                if (!isset($change['type']) || !isset($generators[$change['type']])) {
                    continue;
                }
                $className = 'RectorRule' . $generatedCount;
                $generator = $generators[$change['type']];
                $contents = $generator->generate($change, $className);
                if (isset($contents)) {
                    file_put_contents($outputDir . '/RectorRule' . $generatedCount . '.php', $contents);
                    $generatedCount++;
                }
            }
        }
    }
}
