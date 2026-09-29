<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Tester\Environment;
use Contributte\Tester\Toolkit;
use Contributte\Vite\Exception\LogicalException;
use Contributte\Vite\Service;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';

$createService = static fn (string $manifestFile, bool $devServer): Service => new Service(
	'http://localhost:5173',
	'contributte/vite',
	$manifestFile,
	$devServer,
	'/',
	new Request(new UrlScript('http://localhost/'), cookies: ['contributte/vite' => $devServer ? 'true' : 'false']),
);

// Missing manifest does not break construction
Toolkit::test(static function () use ($createService): void {
	Assert::noError(static function () use ($createService): void {
		$createService(Environment::getTestDir() . '/missing-manifest.json', false);
	});
});

// Missing manifest is not needed when Vite dev server is enabled
Toolkit::test(static function () use ($createService): void {
	$service = $createService(Environment::getTestDir() . '/missing-manifest.json', true);

	Assert::true($service->isEnabled());
	Assert::same('http://localhost:5173/src/scripts/main.js', $service->getAsset('src/scripts/main.js'));
	Assert::same([], iterator_to_array($service->getCssAssets('src/scripts/main.js'), false));
});

// Missing manifest throws once an asset is resolved from it
Toolkit::test(static function () use ($createService): void {
	$manifestFile = Environment::getTestDir() . '/missing-manifest.json';
	$service = $createService($manifestFile, false);

	Assert::false($service->isEnabled());
	Assert::exception(
		static fn () => $service->getAsset('src/scripts/main.js'),
		LogicalException::class,
		sprintf('Missing manifest file "%s".', $manifestFile),
	);
});

// Valid manifest resolves file and css
Toolkit::test(static function () use ($createService): void {
	$manifestFile = Environment::getTestDir() . '/manifest.json';
	file_put_contents($manifestFile, json_encode([
		'src/scripts/main.js' => [
			'file' => 'assets/main-1234.js',
			'css' => ['assets/main-1234.css'],
		],
	]));

	$service = $createService($manifestFile, false);

	Assert::same('/assets/main-1234.js', $service->getAsset('src/scripts/main.js'));
	Assert::same('/assets/main-1234.js', $service->getAsset('/src/scripts/main.js'));
	Assert::same(['/assets/main-1234.css'], iterator_to_array($service->getCssAssets('src/scripts/main.js'), false));
});
