<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassMethod\UnSpreadOperatorRector;
use Rector\CodingStyle\Rector\FuncCall\CallUserFuncArrayToVariadicRector;
use Rector\CodingStyle\Rector\FuncCall\ConsistentPregDelimiterRector;
use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;
use Rector\PHPUnit\Rector\ClassMethod\AddDoesNotPerformAssertionToNonAssertingTestRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Privatization\Rector\Class_\FinalizeClassesWithoutChildrenRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddArrayParamDocTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddArrayReturnDocTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedPropertyRector;
use Rector\TypeDeclaration\Rector\Param\ParamTypeFromStrictTypedPropertyRector;
use Rector\TypeDeclaration\Rector\Property\CompleteVarDocTypePropertyRector;
use Rector\TypeDeclaration\Rector\Property\PropertyTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_72);
    $parameters->set(Option::AUTO_IMPORT_NAMES, true);
    $parameters->set(Option::IMPORT_SHORT_CLASSES, false);

    $parameters->set(Option::PATHS, [
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);
    $parameters->set(Option::AUTOLOAD_PATHS, [
        __DIR__ . '/vendor/php-stubs/wordpress-stubs',
    ]);
    $parameters->set(Option::SKIP, [
        // Files
        '*bootstrap.php',

        // Rules
        AddArrayParamDocTypeRector::class,
        AddArrayReturnDocTypeRector::class,
        AddDoesNotPerformAssertionToNonAssertingTestRector::class,
        CatchExceptionNameMatchingTypeRector::class,
        CompleteVarDocTypePropertyRector::class,
        ConsistentPregDelimiterRector::class,
        CountArrayToEmptyArrayComparisonRector::class,
        PropertyTypeDeclarationRector::class,
        UnSpreadOperatorRector::class,

        // Temporary exclusions
        // Until PHP 7.4
        CallUserFuncArrayToVariadicRector::class,
        ParamTypeFromStrictTypedPropertyRector::class,
        ReturnTypeFromStrictTypedPropertyRector::class,
        TypedPropertyFromStrictConstructorRector::class,
        // Until PHP 8.0
        RemoveUnusedPromotedPropertyRector::class,
        // Until we can refactor
        FinalizeClassesWithoutChildrenRector::class,
    ]);

    // Rule sets
    $containerConfigurator->import(SetList::CODE_QUALITY);
    // $containerConfigurator->import(SetList::CODING_STYLE);
    $containerConfigurator->import(SetList::DEAD_CODE);
    // $containerConfigurator->import(SetList::EARLY_RETURN);
    // $containerConfigurator->import(SetList::PRIVATIZATION);
    $containerConfigurator->import(SetList::PSR_4);
    $containerConfigurator->import(SetList::TYPE_DECLARATION);
    $containerConfigurator->import(SetList::TYPE_DECLARATION_STRICT);

    // PHP version
    $containerConfigurator->import(SetList::PHP_53);
    $containerConfigurator->import(SetList::PHP_54);
    $containerConfigurator->import(SetList::PHP_55);
    $containerConfigurator->import(SetList::PHP_56);
    $containerConfigurator->import(SetList::PHP_70);
    $containerConfigurator->import(SetList::PHP_71);
    $containerConfigurator->import(SetList::PHP_72);

    // PHPUnit
    $containerConfigurator->import(PHPUnitSetList::PHPUNIT_40);
    $containerConfigurator->import(PHPUnitSetList::PHPUNIT_50);
    $containerConfigurator->import(PHPUnitSetList::PHPUNIT_60);
    $containerConfigurator->import(PHPUnitSetList::PHPUNIT_70);
    $containerConfigurator->import(PHPUnitSetList::PHPUNIT_75);
    $containerConfigurator->import(PHPUnitSetList::PHPUNIT_80);
    $containerConfigurator->import(PHPUnitSetList::PHPUNIT_CODE_QUALITY);
    $containerConfigurator->import(PHPUnitSetList::PHPUNIT_EXCEPTION);
    $containerConfigurator->import(PHPUnitSetList::PHPUNIT_SPECIFIC_METHOD);
    $containerConfigurator->import(PHPUnitSetList::PHPUNIT_YIELD_DATA_PROVIDER);
};
