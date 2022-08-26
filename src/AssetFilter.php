<?php

declare(strict_types=1);

namespace Contributte\Vite;

final class AssetFilter
{
	private Service $vite;

	public function __construct(Service $vite)
	{
		$this->vite = $vite;
	}

	public function __invoke(string $path): string
	{
		return $this->vite->getAsset($path);
	}
}
