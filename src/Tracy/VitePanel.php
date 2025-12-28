<?php declare(strict_types = 1);

namespace Contributte\Vite\Tracy;

use Contributte\Vite\Service;
use Tracy\IBarPanel;

final class VitePanel implements IBarPanel
{

	private Service $vite;

	public function __construct(Service $vite)
	{
		$this->vite = $vite;
	}

	public function getTab(): string
	{
		$html = (string) file_get_contents(__DIR__ . '/Vite.html');

		return str_replace('%viteCookie%', $this->vite->getViteCookie(), $html);
	}

	public function getPanel(): string
	{
		return '';
	}

}
