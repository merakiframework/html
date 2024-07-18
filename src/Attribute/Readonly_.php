<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Readonly_ extends Attribute implements Boolean
{
	public function __construct()
	{
		$this->setName('readonly');
		$this->setValue('');
	}
}
