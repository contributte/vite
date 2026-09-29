<?php declare(strict_types = 1);

namespace Tests\Cases\Override;

use Contributte\Tester\Environment;
use Contributte\Tester\Toolkit;
use Contributte\Tester\Utils\ContainerBuilder;
use Contributte\Tester\Utils\Neonkit;
use Contributte\Vite\Exception\LogicalException;
use Contributte\Vite\Nette\Extension;
use Contributte\Vite\Service;
use Nette\Bridges\ApplicationDI\ApplicationExtension;
use Nette\Bridges\ApplicationDI\LatteExtension;
use Nette\Bridges\ApplicationDI\RoutingExtension;
use Nette\Bridges\HttpDI\HttpExtension;
use Nette\DI\Compiler;
use Nette\Utils\FileSystem;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(static function (): void {
	file_put_contents(Environment::getTestDir() . '/manifest.json', '{}');

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

// Missing manifest with automatic search does not break container
Toolkit::test(static function (): void {
	$wwwDir = Environment::getTestDir() . '/missing-auto';
	FileSystem::createDir($wwwDir);

	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler) use ($wwwDir): void {
			$compiler->addExtension('vite', new Extension());
			$compiler->addExtension('application', new ApplicationExtension());
			$compiler->addExtension('http', new HttpExtension());
			$compiler->addExtension('routing', new RoutingExtension());
			$compiler->addExtension('latte', new LatteExtension(Environment::getTestDir()));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				vite:
					debugMode: false
			NEON
			));
			$compiler->addConfig([
				'parameters' => [
					'wwwDir' => $wwwDir,
				],
			]);
		})->build();

	$service = $container->getByType(Service::class);
	Assert::type(Service::class, $service);
	Assert::exception(
		static fn () => $service->getAsset('src/scripts/main.js'),
		LogicalException::class,
		sprintf('Missing manifest file "%s".', $wwwDir . DIRECTORY_SEPARATOR . 'manifest.json'),
	);
});

// Missing explicit manifest does not break container
Toolkit::test(static function (): void {
	$wwwDir = Environment::getTestDir() . '/missing-explicit';
	FileSystem::createDir($wwwDir);

	$container = ContainerBuilder::of()
		->withCompiler(function (Compiler $compiler) use ($wwwDir): void {
			$compiler->addExtension('vite', new Extension());
			$compiler->addExtension('application', new ApplicationExtension());
			$compiler->addExtension('http', new HttpExtension());
			$compiler->addExtension('routing', new RoutingExtension());
			$compiler->addExtension('latte', new LatteExtension(Environment::getTestDir()));
			$compiler->addConfig(Neonkit::load(<<<'NEON'
				vite:
					debugMode: false
					manifestFile: %wwwDir%/manifest.json
			NEON
			));
			$compiler->addConfig([
				'parameters' => [
					'wwwDir' => $wwwDir,
				],
			]);
		})->build();

	$service = $container->getByType(Service::class);
	Assert::type(Service::class, $service);
	Assert::exception(
		static fn () => $service->getAsset('src/scripts/main.js'),
		LogicalException::class,
		sprintf('Missing manifest file "%s".', $wwwDir . '/manifest.json'),
	);
});
