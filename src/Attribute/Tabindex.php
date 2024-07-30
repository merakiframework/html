<?php
declare(strict_types=1);

namespace Meraki\Html\Attribute;

use Meraki\Html\Attribute;

final class Tabindex extends Attribute
{
	public function __construct(int|string $value)
	{
		parent::__construct('tabindex', (string)$value);
	}

	public static function of(int|string $index): self
	{
		return new self((string) $index);
	}

	public function isTabbable(): bool
	{
		return $this->value !== '-1';
	}
}
