<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
	$services = $ecsConfig->services();
	$ecsConfig->paths([__DIR__ . '/src']);
	$ecsConfig->sets([SetList::CLEAN_CODE, SetList::PSR_12]);

	$ecsConfig->indentation('tab');

	$services->set(SingleQuoteFixer::class);
	$services->set(ClassAttributesSeparationFixer::class);
	$services->set(PhpdocLineSpanFixer::class)
		->call('configure', [[
			'property' => 'single'
		]]);
};
