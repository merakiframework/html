<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Role extends Attribute
{
	public function __construct(string $value)
	{
		$this->setName('role');
		$this->setValue($value);
	}
}
