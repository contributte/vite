<?php declare(strict_types = 1);

namespace Tests\Cases\Override;

use Contributte\Tester\Environment;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Contributte\Vite\Nette\Extension;
use Contributte\Vite\Service;
use Nette\Bridges\ApplicationDI\ApplicationExtension;
use Nette\Bridges\ApplicationDI\LatteExtension;
use Nette\Bridges\ApplicationDI\RoutingExtension;
use Nette\Bridges\HttpDI\HttpExtension;
use Nette\DI\Compiler;
use Nette\Safe;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(static function (): void {
	Safe::touch(Environment::getTestDir() . '/manifest.json');

	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler): void {
			$compiler->addExtension('vite', new Extension());
			$compiler->addExtension('application', new ApplicationExtension());
			$compiler->addExtension('http', new HttpExtension());
			$compiler->addExtension('routing', new RoutingExtension());
			$compiler->addExtension('latte', new LatteExtension(Environment::getTestDir()));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				vite:
					manifestFile: %wwwDir%/manifest.json
			NEON
			));
			$compiler->addConfig([
				'parameters' => [
					'wwwDir' => Environment::getTestDir(),
				],
			]);
		})->build();

	Assert::type(Service::class, $container->getByType(Service::class));
});
