<?php

declare(strict_types=1);

namespace Contributte\Vite\Tracy;

use Contributte\Vite\Service;
use Nette\Safe;
use Tracy;

final class VitePanel implements Tracy\IBarPanel
{
	private Service $vite;

	public function __construct(Service $vite)
	{
		$this->vite = $vite;
	}

	public function getTab()
	{
		$html = Safe::file_get_contents(__DIR__ . '/Vite.html');

		return str_replace('%viteCookie%', $this->vite->getViteCookie(), $html);
	}

	public function getPanel()
	{
		return '';
	}
}
