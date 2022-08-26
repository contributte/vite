<?php declare(strict_types=1);

namespace Contributte\Vite\Tracy;

use Nette\Safe;
use Tracy;

final class VitePanel implements Tracy\IBarPanel
{
	public function getTab()
	{
		return Safe::file_get_contents(__DIR__ . '/Vite.html');
	}


	public function getPanel()
	{
		return '';
	}

}
