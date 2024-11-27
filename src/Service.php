<?php declare(strict_types = 1);

namespace Contributte\Vite;

use Contributte\Vite\Exception\LogicalException;
use Generator;
use Nette\Http\Request;
use Nette\Utils\FileSystem;
use Nette\Utils\Html;

final class Service
{

	private string $viteServer;

	private string $viteCookie;

	private string $manifestFile;

	private bool $debugMode;

	private string $basePath;

	private Request $httpRequest;

	/** @var array<array<mixed>> $manifest */
	private array $manifest;

	public function __construct(
		string $viteServer,
		string $viteCookie,
		string $manifestFile,
		bool $debugMode,
		string $basePath,
		Request $httpRequest
	)
	{
		$this->viteServer = $viteServer;
		$this->viteCookie = $viteCookie;
		$this->manifestFile = $manifestFile;
		$this->debugMode = $debugMode;
		$this->basePath = $basePath;
		$this->httpRequest = $httpRequest;

		if (!file_exists($this->manifestFile)) {
			trigger_error('Missing manifest file: ' . $this->manifestFile, E_USER_WARNING);
		}

		/** @var array<array<mixed>> $manifest */
		$manifest = json_decode(FileSystem::read($this->manifestFile), true);
		$this->manifest = $manifest;
	}

	/**
	 * @return array<string, array<array<string, string>>>
	 */
	private function getEndpointManifest(string $entrypoint): array {
		$entrypoint = ltrim($entrypoint, '/');
		/** @var array<string, array<array<string, string>>> $manifest */
		$manifest = $this->manifest[$entrypoint];

		return $manifest;
	}

	public function getAsset(string $entrypoint): string
	{
		if (str_starts_with($entrypoint, 'http')) {
			return $entrypoint;
		}

		if ($this->isEnabled()) {
			$baseUrl = $this->viteServer . '/';
			$asset = $entrypoint;
		} else {
			$baseUrl = $this->basePath;
			$asset = $this->getEndpointManifest($entrypoint)['file'] ?? throw new LogicalException('Invalid manifest');
		}

		return $baseUrl . $asset;
	}

	/**
	 * @return Generator<string>
	 */
	public function getCssAssets(string $entrypoint, bool $withNestedCss = false): Generator
	{
		if (!$this->isEnabled()) {
			$manifest = $this->getEndpointManifest($entrypoint);
			yield from $manifest['css'] ?? [];

			if ($withNestedCss) {
				$imports = $manifest['imports'] ?? [];
				foreach ($imports as $import) {
					yield from $this->getCssAssets($import, $withNestedCss);
				}
			}
		}
	}

	/**
	 * @return array<string, string>
	 */
	public function getImports(string $entrypoint): array
	{
		$assets = [];

		if (!$this->isEnabled()) {
			$assets = $this->getEndpointManifest($entrypoint)['imports'] ?? [];
		}

		return $assets;
	}

	/**
	 * @return array<string, string>
	 */
	public function getDynamicImports(string $entrypoint): array
	{
		$assets = [];

		if (!$this->isEnabled()) {
			$assets = $this->getEndpointManifest($entrypoint)['dynamicImports'] ?? [];
		}

		return $assets;
	}

	public function isEnabled(): bool
	{
		return $this->debugMode && $this->httpRequest->getCookie($this->viteCookie) === 'true';
	}

	/**
	 * @return Generator<Html>
	 */
	public function getTags(string $entrypoint, bool $withNestedCss = false): Generator
	{
		$scripts = [$this->getAsset($entrypoint)];
		$styles = $this->getCssAssets($entrypoint, $withNestedCss);

		if ($this->isEnabled()) {
			yield Html::el('script')->type('module')->src($this->viteServer . '/@vite/client');
		}

		foreach ($styles as $path) {
			yield Html::el('link')->rel('stylesheet')->href($path);
		}

		foreach ($scripts as $path) {
			yield Html::el('script')->type('module')->src($path);
		}
	}

	public function printTags(string $entrypoint, bool $withNestedCss = false): void
	{
		foreach ($this->getTags($entrypoint, $withNestedCss) as $tag) {
			echo $tag;
		}
	}

	public function getViteCookie(): string
	{
		return $this->viteCookie;
	}

}
