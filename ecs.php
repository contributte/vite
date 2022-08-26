<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
	$services = $containerConfigurator->services();
	$parameters = $containerConfigurator->parameters();

	$containerConfigurator->paths([__DIR__ . '/src']);

	$parameters->set(Option::INDENTATION_TAB);
	$parameters->set([SetList::CLEAN_CODE, SetList::PSR_12]);
	$services->set(SingleQuoteFixer::class);
	$services->set(ClassAttributesSeparationFixer::class);
	$services->set(PhpdocLineSpanFixer::class)
		->call('configure', [[
			'property' => 'single'
		]]);
};
