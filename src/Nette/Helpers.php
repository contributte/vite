<?php declare(strict_types = 1);

namespace Contributte\Vite\Nette;

use Contributte\Vite\Service;
use Nette\Application\UI\Template;

final class Helpers
{

	public static function prepareTemplate(string $propertyName, Service $service): \Closure
	{
		return static function (Template $template) use ($propertyName, $service): void {
			$template->{$propertyName} = $service;
		};
	}

}
