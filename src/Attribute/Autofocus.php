<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Autofocus extends Attribute
{
	public function __construct()
	{
		$this->setName('autofocus');
		$this->setValue(true);
	}
}
