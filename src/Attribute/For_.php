<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class For_ extends Attribute
{
	public function __construct(string|Attribute\Id $id)
	{
		$this->setName('for');
		$this->setValue($id instanceof Attribute\Id ? $id->value : $id);
	}
}
