<?php declare(strict_types = 1);

namespace Contributte\Vite\Nette;

use Contributte\Vite\AssetFilter;
use Contributte\Vite\Exception\LogicalException;
use Contributte\Vite\Service;
use Contributte\Vite\Tracy\VitePanel;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\Safe;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\Finder;
use stdClass;
use Tracy\Bar;

/**
 * @property stdClass $config
 */
final class Extension extends CompilerExtension
{

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'server' => Expect::string('http://localhost:5173'),
			'cookie' => Expect::string('contributte/vite'),
			'debugMode' => Expect::bool($this->getContainerBuilder()->parameters['debugMode'] ?? false),
			'manifestFile' => Expect::string(),
			'filterName' => Expect::string('vite'), // empty string is for disabled
			'templateProperty' => Expect::string('vite'), // empty string is for disabled
			'wwwDir' => Expect::string($this->getContainerBuilder()->parameters['wwwDir'] ?? getcwd()),
			'basePath' => Expect::string(),
		]);
	}

	public function loadConfiguration(): void
	{
		$this->buildViteService();
		$this->buildFilter();
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$templateFactoryDefinition = $builder->getDefinition('latte.templateFactory');
		assert($templateFactoryDefinition instanceof ServiceDefinition);

		if ($this->config->templateProperty !== '') {
			$templateFactoryDefinition->addSetup(
				new Statement('$onCreate[]', [
					new Statement([
						Helpers::class,
						'prepareTemplate',
					], [$this->config->templateProperty, $builder->getDefinition($this->prefix('service'))]),
				]),
			);
		}

		if ($this->config->filterName !== '' && $builder->hasDefinition('latte.latteFactory')) {
			$definition = $builder->getDefinition('latte.latteFactory');
			assert($definition instanceof FactoryDefinition);
			$definition->getResultDefinition()
				->addSetup('addFilter', [
					$this->config->filterName,
					$builder->getDefinition($this->prefix('assetFilter')),
				]);
		}

		if ($this->config->debugMode && $builder->getByType(Bar::class) !== null) {
			$definition = $this->getContainerBuilder()
				->getDefinition($this->prefix('service'));

			assert($definition instanceof ServiceDefinition);

			$definition->addSetup(sprintf('@%s::addPanel', Bar::class), [
				new Statement(VitePanel::class),
			]);
		}
	}

	private function buildViteService(): void
	{
		$manifestFile = $this->prepareManifestPath();
		$this->getContainerBuilder()->addDefinition($this->prefix('service'))
			->setFactory(Service::class)
			->setArguments([
				'viteServer' => $this->config->server,
				'viteCookie' => $this->config->cookie,
				'manifestFile' => $manifestFile,
				'debugMode' => $this->config->debugMode,
				'basePath' => $this->prepareBasePath($manifestFile),
			]);
	}

	private function buildFilter(): void
	{
		$this->getContainerBuilder()->addDefinition($this->prefix('assetFilter'))
			->setFactory(AssetFilter::class)
			->setAutowired(false);
	}

	private function prepareManifestPath(): string
	{
		if ($this->config->manifestFile === null) {
			return $this->automaticSearchManifestFile();
		}

		$manifestFile = $this->config->manifestFile;
		if (!is_file($manifestFile)) {
			$newPath = $this->config->wwwDir . DIRECTORY_SEPARATOR . ltrim($manifestFile, '/\\');
			if (!is_file($newPath)) {
				throw new LogicalException(sprintf('Found here "%s" or "%s".', $manifestFile, $newPath));
			}

			$manifestFile = $newPath;
		}

		return Safe::realpath($manifestFile);
	}

	private function prepareBasePath(string $manifestFile): string
	{
		if ($this->config->basePath === null) {
			return str_replace(Safe::realpath($this->config->wwwDir), '', dirname($manifestFile)) . '/';
		}

		return $this->config->basePath;
	}

	private function automaticSearchManifestFile(): string
	{
		$finder = Finder::findFiles('manifest.json')->from($this->config->wwwDir);
		$files = [];
		foreach ($finder as $file) {
			$files[] = $file->getPathname();
		}

		if ($files === []) {
			throw new LogicalException(sprintf('Define path to manifest.json, because automatic search found nothing in "%s".', $this->config->wwwDir));
		} elseif (count($files) > 1) {
			throw new LogicalException(sprintf('Define path to manifest.json, because automatic search found many manifest.json files %s.', implode(', ', $files)));
		}

		return reset($files);
	}

}
