<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Mask extends Attribute
{
	public function __construct(string $value)
	{
		$this->setName('mask');
		$this->setValue($value);
	}
}
