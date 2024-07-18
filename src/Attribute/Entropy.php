<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Entropy extends Attribute
{
	public function __construct(int $value)
	{
		$this->setName('entropy');
		$this->setValue($value);
	}

	public static function of(int $value): self
	{
		return new self($value);
	}
}
