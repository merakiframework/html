<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Accesskey extends Attribute
{
	public function __construct(string $key)
	{
		$this->setName('accesskey');
		$this->setValue($key);
	}
}
