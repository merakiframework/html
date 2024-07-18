<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Id extends Attribute
{
	public function __construct(string $value)
	{
		$this->setName('id');
		$this->setValue($value);
	}

	public static function generateRandom(string $prefix = ''): self
	{
		return new self($prefix . bin2hex(random_bytes(8)));
	}

	protected function setValue(mixed $value): void
	{
		$value = trim($value);

		if (empty($value)) {
			throw new \InvalidArgumentException('The "id" attribute can not be empty.');
		}

		parent::setValue($value);
	}
}
