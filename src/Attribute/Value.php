<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Value extends Attribute
{
	public function __construct(bool|int|string|\Stringable|null $value)
	{
		$this->setName('value');
		$this->setValue($value);
	}
}
