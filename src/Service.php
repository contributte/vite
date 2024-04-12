<?php declare(strict_types = 1);

namespace Contributte\Vite;

use Contributte\Vite\Exception\LogicalException;
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

	public function getAsset(string $entrypoint): string
	{
		if (str_starts_with($entrypoint, 'http')) {
			return $entrypoint;
		}

		if ($this->isEnabled()) {
			$baseUrl = $this->viteServer . '/';
			$asset = $entrypoint;
		} else {
			$entrypoint = ltrim($entrypoint, '/');
			$baseUrl = $this->basePath;
			/** @var array<array<mixed>> $manifest */
			$manifest = $this->manifest;
			$asset = $manifest[$entrypoint]['file'] ?? throw new LogicalException('Invalid manifest');
		}

		return $baseUrl . $asset;
	}

	/**
	 * @return array<string, string>
	 */
	public function getCssAssets(string $entrypoint): array
	{
		$assets = [];

		if (!$this->isEnabled()) {
			$entrypoint = ltrim($entrypoint, '/');
			/** @var array<string, array<array<string, string>>> $manifest */
			$manifest = $this->manifest[$entrypoint];
			$assets = $manifest[$entrypoint]['css'] ?? [];
		}

		return $assets;
	}

	public function isEnabled(): bool
	{
		return $this->debugMode && $this->httpRequest->getCookie($this->viteCookie) === 'true';
	}

	public function printTags(string $entrypoint): void
	{
		$scripts = [$this->getAsset($entrypoint)];
		$styles = $this->getCssAssets($entrypoint);

		if ($this->isEnabled()) {
			echo Html::el('script')->type('module')->src($this->viteServer . '/@vite/client');
		}

		foreach ($styles as $path) {
			echo Html::el('link')->rel('stylesheet')->href($path);
		}

		foreach ($scripts as $path) {
			echo Html::el('script')->type('module')->src($path);
		}
	}

	public function getViteCookie(): string
	{
		return $this->viteCookie;
	}

}
