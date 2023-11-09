<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Assign\CombinedAssignRector;
use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\Foreach_\ForeachToInArrayRector;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodeQuality\Rector\If_\ConsecutiveNullCompareReturnsToNullCoalesceQueueRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodingStyle\Rector\PostInc\PostIncDecToPreIncDecRector;
use Rector\Config\RectorConfig;
use Rector\EarlyReturn\Rector\If_\ChangeAndIfToEarlyReturnRector;
use Rector\Instanceof_\Rector\Ternary\FlipNegatedTernaryInstanceofRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationBasedOnParentClassMethodRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\Property\AddPropertyTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/public',
        __DIR__.'/resources',
        __DIR__.'/routes',
    ]);
    $rectorConfig->skip([
        PostIncDecToPreIncDecRector::class,
    ]);

    // register a single rule
    //    $rectorConfig->rules([
    //        InlineConstructorDefaultToPropertyRector::class,
    //        AddMethodCallBasedStrictParamTypeRector::class,
    //        AddPropertyTypeDeclarationRector::class,
    //        AddReturnTypeDeclarationBasedOnParentClassMethodRector::class,
    //        AddReturnTypeDeclarationRector::class,
    //        AddVoidReturnTypeWhereNoReturnRector::class,
    //        DeclareStrictTypesRector::class,
    //        ReturnTypeFromReturnNewRector::class,
    //        ChangeAndIfToEarlyReturnRector::class,
    //        FlipNegatedTernaryInstanceofRector::class,
    //        CombineIfRector::class,
    //        CombinedAssignRector::class,
    //        CompleteDynamicPropertiesRector::class,
    //        ConsecutiveNullCompareReturnsToNullCoalesceQueueRector::class,
    //        ExplicitBoolCompareRector::class,
    //        ForeachToInArrayRector::class,
    //    ]);
    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        SetList::CODE_QUALITY,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
        SetList::CODING_STYLE,
        SetList::STRICT_BOOLEANS,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
        SetList::GMAGICK_TO_IMAGICK,
    ]);
};
