<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Novalidate extends Attribute implements Boolean
{
	public function __construct()
	{
		$this->setName('novalidate');
		$this->setValue('');
	}
}
